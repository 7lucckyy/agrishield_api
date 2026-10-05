import 'dart:io';

import 'package:path/path.dart' as path;
import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';

import '../api/api_client.dart';
import 'offline_database.dart';

class OfflineSubmissionRepository {
  const OfflineSubmissionRepository(
    this._api,
    this._database, {
    this.documentsDirectory = getApplicationDocumentsDirectory,
  });

  final ApiClient _api;
  final AccountOfflineStore _database;
  final Future<Directory> Function() documentsDirectory;

  void _ensureActiveOwner() {
    if (_api.authenticatedUserId != _database.userId) {
      throw const ApiException(
        'Sign in to the account that owns this saved request.',
      );
    }
  }

  Future<Map<String, dynamic>> submitDiagnosis({
    required String farmId,
    required String imagePath,
    String? note,
  }) async {
    _ensureActiveOwner();
    final localId = const Uuid().v4();
    try {
      return await _api.submitDiagnosis(
        farmId: farmId,
        imagePath: imagePath,
        note: note,
        idempotencyKey: localId,
      );
    } on ApiException catch (error) {
      if (error.statusCode != null) rethrow;
      _ensureActiveOwner();
      final durablePath = await _copyToOutbox(imagePath, localId);
      await _database.enqueue(
        localId: localId,
        entityType: 'diagnosis',
        operation: 'create',
        payload: {'farm_id': farmId, 'note': note},
        mediaPath: durablePath,
      );
      return {
        'local_id': localId,
        'status': SyncState.pending.name,
        'diagnosis': 'Saved on this device',
        'recommendation': 'Your crop photo is pending sync and will upload when AgriShield can reach the server.',
      };
    }
  }

  Future<Map<String, dynamic>> submitVoice({
    required String audioPath,
    required String sourceLanguage,
    required String responseLanguage,
    String? farmId,
  }) async {
    _ensureActiveOwner();
    final localId = const Uuid().v4();
    try {
      return await _api.submitVoice(
        path: audioPath,
        sourceLanguage: sourceLanguage,
        responseLanguage: responseLanguage,
        farmId: farmId,
        idempotencyKey: localId,
      );
    } on ApiException catch (error) {
      if (error.statusCode != null) rethrow;
      _ensureActiveOwner();
      final durablePath = await _copyToOutbox(audioPath, localId);
      await _database.enqueue(
        localId: localId,
        entityType: 'voice',
        operation: 'create',
        payload: {
          'farm_id': farmId,
          'source_language': sourceLanguage,
          'response_language': responseLanguage,
        },
        mediaPath: durablePath,
      );
      return {
        'local_id': localId,
        'status': SyncState.pending.name,
        'guidance':
            'Your recording is saved on this device and is pending sync.',
      };
    }
  }

  Future<String> _copyToOutbox(String sourcePath, String localId) async {
    final source = File(sourcePath);
    if (!await source.exists()) {
      throw const ApiException(
        'The selected media file is no longer available.',
      );
    }
    final root = await documentsDirectory();
    final directory = Directory(
      path.join(root.path, 'agrishield_outbox', '${_database.userId}'),
    );
    await directory.create(recursive: true);
    final extension = path.extension(sourcePath);
    final destination = path.join(directory.path, '$localId$extension');
    if (path.equals(source.absolute.path, File(destination).absolute.path)) {
      return destination;
    }
    return (await source.copy(destination)).path;
  }
}

class OutboxSyncService {
  const OutboxSyncService(
    this._api,
    this._database, {
    this.documentsDirectory = getApplicationDocumentsDirectory,
  });

  final ApiClient _api;
  final AccountOfflineStore _database;
  final Future<Directory> Function() documentsDirectory;

  bool get _isActiveOwner => _api.authenticatedUserId == _database.userId;

  Future<void> synchronize() async {
    if (!_isActiveOwner) return;
    await _cleanupSyncedMedia();
    final operations = await _database.pendingOperations();
    for (final operation in operations) {
      if (!_isActiveOwner) return;
      await _synchronize(operation);
    }
  }

  Future<void> _synchronize(OutboxOperation operation) async {
    if (!_isActiveOwner || operation.ownerUserId != _database.userId) return;
    if (!await _database.claimForSync(operation.localId)) return;
    if (!_isActiveOwner) {
      await _database.retry(operation.localId);
      return;
    }
    try {
      final result = switch (operation.entityType) {
        'diagnosis' => await _api.submitDiagnosis(
          farmId: operation.payload['farm_id']! as String,
          imagePath: operation.mediaPath!,
          note: operation.payload['note'] as String?,
          idempotencyKey: operation.localId,
        ),
        'voice' => await _api.submitVoice(
          path: operation.mediaPath!,
          sourceLanguage: operation.payload['source_language']! as String,
          responseLanguage: operation.payload['response_language']! as String,
          farmId: operation.payload['farm_id'] as String?,
          idempotencyKey: operation.localId,
        ),
        _ => throw ApiException(
          'Unsupported offline operation: ${operation.entityType}.',
          statusCode: 422,
        ),
      };
      await _database.markSynced(
        operation.localId,
        serverId: result['id']?.toString(),
      );
    } on ApiException catch (error) {
      if (error.statusCode == 409) {
        await _database.markConflict(operation.localId, error.message);
      } else {
        await _database.markFailed(operation.localId, error.message);
      }
    } catch (error) {
      await _database.markFailed(operation.localId, error.toString());
    }
    await _cleanupSyncedMedia();
  }

  Future<void> _cleanupSyncedMedia() async {
    final operations = await _database.syncedMediaOperations();
    if (operations.isEmpty) return;
    final root = await documentsDirectory();
    final accountDirectory = path.join(
      root.path,
      'agrishield_outbox',
      '${_database.userId}',
    );
    for (final operation in operations) {
      if (!_isActiveOwner) return;
      final mediaPath = operation.mediaPath;
      if (mediaPath == null) continue;
      if (!path.equals(path.dirname(mediaPath), accountDirectory) ||
          !path.basename(mediaPath).startsWith('${operation.localId}.')) {
        continue;
      }
      try {
        final media = File(mediaPath);
        if (await media.exists()) await media.delete();
        await _database.clearSyncedMediaPath(operation.localId);
      } on FileSystemException {
        // Keep the path so cleanup can retry after a later sync.
      }
    }
  }
}
