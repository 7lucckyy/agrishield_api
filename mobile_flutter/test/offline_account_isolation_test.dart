import 'dart:io';

import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/storage/farm_repository.dart';
import 'package:agrishield_ai/core/storage/offline_database.dart';
import 'package:agrishield_ai/core/storage/offline_submission_repository.dart';
import 'package:agrishield_ai/models/models.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:path/path.dart' as path;
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

class _OfflineApiClient extends ApiClient {
  bool online = false;
  Farm? farmResponse;
  final List<(int?, String, String?)> submissions = [];

  @override
  Future<List<Farm>> farms({bool activeOnly = false}) async {
    throw const ApiException('No connection');
  }

  @override
  Future<Farm> farm(String id) async {
    if (!online || farmResponse == null) {
      throw const ApiException('No connection');
    }
    return farmResponse!;
  }

  @override
  Future<Json> submitDiagnosis({
    required String farmId,
    required String imagePath,
    String? note,
    String? idempotencyKey,
  }) async {
    if (!online) throw const ApiException('No connection');
    submissions.add((authenticatedUserId, 'diagnosis', idempotencyKey));
    return {'id': 'diagnosis-server-id'};
  }

  @override
  Future<Json> submitVoice({
    required String path,
    required String sourceLanguage,
    required String responseLanguage,
    String? farmId,
    String? idempotencyKey,
  }) async {
    if (!online) throw const ApiException('No connection');
    submissions.add((authenticatedUserId, 'voice', idempotencyKey));
    return {'id': 'voice-server-id'};
  }
}

void main() {
  setUpAll(sqfliteFfiInit);

  test(
    'saved farm detail retains sections and crop context for its owner only',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'agrishield-detail-',
      );
      final api = _OfflineApiClient()
        ..online = true
        ..farmResponse = const Farm(
          id: 'farm-a',
          name: 'North farm',
          status: 'active',
          sections: [
            FarmSection(
              id: 4,
              name: 'Onion field',
              crop: Crop(id: 3, name: 'Onion'),
              hectares: 1.2,
            ),
          ],
          activeCropCycle: CropCycle(status: 'active', cropName: 'Onion'),
        )
        ..setToken('token-a', ownerUserId: 1);
      var database = await OfflineDatabase.open(
        factory: databaseFactoryFfi,
        databasePath: path.join(directory.path, 'offline.db'),
      );

      try {
        final online = await FarmRepository(
          api,
          database.forUser(1),
        ).detail('farm-a');
        expect(online.isOffline, isFalse);
        await database.close();
        database = await OfflineDatabase.open(
          factory: databaseFactoryFfi,
          databasePath: path.join(directory.path, 'offline.db'),
        );
        api.online = false;

        final saved = await FarmRepository(
          api,
          database.forUser(1),
        ).detail('farm-a');
        expect(saved.isOffline, isTrue);
        expect(saved.farm.sections.single.crop.name, 'Onion');
        expect(saved.farm.activeCropCycle?.cropName, 'Onion');

        api.setToken('token-b', ownerUserId: 2);
        await expectLater(
          FarmRepository(api, database.forUser(2)).detail('farm-a'),
          throwsA(isA<ApiException>()),
        );
      } finally {
        await database.close();
        await directory.delete(recursive: true);
      }
    },
  );

  test(
    'account switching cannot read or replay another user’s offline evidence',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'agrishield-account-',
      );
      final databasePath = path.join(directory.path, 'offline.db');
      var database = await OfflineDatabase.open(
        factory: databaseFactoryFfi,
        databasePath: databasePath,
      );
      try {
        final api = _OfflineApiClient()..setToken('token-a', ownerUserId: 1);
        final accountA = database.forUser(1);
        await accountA.replaceCollection('farms', [
          const Farm(id: 'farm-a', name: 'A’s farm', status: 'active').toJson(),
        ]);

        final photo = File(path.join(directory.path, 'photo.jpg'));
        final voice = File(path.join(directory.path, 'voice.m4a'));
        await photo.writeAsBytes([1, 2, 3]);
        await voice.writeAsBytes([4, 5, 6]);
        final submissions = OfflineSubmissionRepository(
          api,
          accountA,
          documentsDirectory: () async => directory,
        );
        final queuedPhoto = await submissions.submitDiagnosis(
          farmId: 'farm-a',
          imagePath: photo.path,
        );
        final queuedVoice = await submissions.submitVoice(
          audioPath: voice.path,
          sourceLanguage: 'ha',
          responseLanguage: 'ha',
        );
        final pendingA = await accountA.pendingOperations();
        expect(pendingA, hasLength(2));
        expect(pendingA.every((item) => item.ownerUserId == 1), isTrue);
        expect(
          pendingA.every((item) => File(item.mediaPath!).existsSync()),
          isTrue,
        );

        api.setToken(null);
        api.setToken('token-b', ownerUserId: 2);
        final accountB = database.forUser(2);
        expect(await accountB.collection('farms'), isNull);
        expect(await accountB.pendingOperations(), isEmpty);
        expect(
          await accountB.operationById(queuedPhoto['local_id'] as String),
          isNull,
        );
        expect(
          await accountB.operationById(queuedVoice['local_id'] as String),
          isNull,
        );
        await expectLater(
          FarmRepository(api, accountA).list(),
          throwsA(isA<ApiException>()),
        );
        await expectLater(
          submissions.submitVoice(
            audioPath: voice.path,
            sourceLanguage: 'ha',
            responseLanguage: 'ha',
          ),
          throwsA(isA<ApiException>()),
        );
        api.online = true;
        await OutboxSyncService(api, accountA).synchronize();
        await OutboxSyncService(api, accountB).synchronize();
        expect(api.submissions, isEmpty);

        await database.close();
        database = await OfflineDatabase.open(
          factory: databaseFactoryFfi,
          databasePath: databasePath,
        );
        api.setToken('token-a-again', ownerUserId: 1);
        final recoveredA = database.forUser(1);
        final cached = await FarmRepository(api, recoveredA).list();
        expect(cached.isOffline, isTrue);
        expect(cached.farms.single.name, 'A’s farm');
        expect(await recoveredA.pendingOperations(), hasLength(2));

        await OutboxSyncService(
          api,
          recoveredA,
          documentsDirectory: () async => directory,
        ).synchronize();
        await OutboxSyncService(
          api,
          recoveredA,
          documentsDirectory: () async => directory,
        ).synchronize();
        expect(api.submissions, hasLength(2));
        expect(api.submissions.every((item) => item.$1 == 1), isTrue);
        expect(api.submissions.map((item) => item.$2).toSet(), {
          'diagnosis',
          'voice',
        });
        expect(api.submissions.map((item) => item.$3).toSet(), {
          queuedPhoto['local_id'],
          queuedVoice['local_id'],
        });
        expect(await recoveredA.pendingOperations(), isEmpty);
        expect(File(pendingA.first.mediaPath!).existsSync(), isFalse);
        expect(File(pendingA.last.mediaPath!).existsSync(), isFalse);
      } finally {
        await database.close();
        await directory.delete(recursive: true);
      }
    },
  );

  test(
    'unowned version-one rows are discarded during the security upgrade',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'agrishield-legacy-',
      );
      final databasePath = path.join(directory.path, 'offline.db');
      final legacyMediaDirectory = Directory(
        path.join(directory.path, 'agrishield_outbox'),
      );
      await legacyMediaDirectory.create();
      final legacyMedia = File(
        path.join(
          legacyMediaDirectory.path,
          '123e4567-e89b-12d3-a456-426614174000.webm',
        ),
      );
      await legacyMedia.writeAsBytes([1, 2, 3]);
      final retainedAccountMedia = File(
        path.join(legacyMediaDirectory.path, '1', 'owned.webm'),
      );
      await retainedAccountMedia.parent.create();
      await retainedAccountMedia.writeAsBytes([4, 5, 6]);
      final legacy = await databaseFactoryFfi.openDatabase(
        databasePath,
        options: OpenDatabaseOptions(
          version: 1,
          onCreate: (database, _) async {
            await database.execute('''
            CREATE TABLE cached_records (
              collection_key TEXT NOT NULL,
              record_key TEXT NOT NULL,
              payload_json TEXT NOT NULL,
              updated_at TEXT NOT NULL,
              PRIMARY KEY (collection_key, record_key)
            )
          ''');
            await database.execute('''
            CREATE TABLE outbox_operations (
              local_id TEXT PRIMARY KEY,
              entity_type TEXT NOT NULL,
              operation_type TEXT NOT NULL,
              payload_json TEXT NOT NULL,
              created_at TEXT NOT NULL,
              updated_at TEXT NOT NULL,
              status TEXT NOT NULL
            )
          ''');
            await database.insert('cached_records', {
              'collection_key': 'farms',
              'record_key': 'unknown-owner',
              'payload_json': '{"id":"unknown-owner"}',
              'updated_at': '2026-10-03T00:00:00Z',
            });
            await database.insert('outbox_operations', {
              'local_id': 'unknown-owner-voice',
              'entity_type': 'voice',
              'operation_type': 'create',
              'payload_json': '{}',
              'created_at': '2026-10-03T00:00:00Z',
              'updated_at': '2026-10-03T00:00:00Z',
              'status': 'pending',
            });
          },
        ),
      );
      await legacy.close();

      final upgraded = await OfflineDatabase.open(
        factory: databaseFactoryFfi,
        databasePath: databasePath,
        documentsDirectory: () async => directory,
      );
      try {
        expect(await upgraded.forUser(1).collection('farms'), isNull);
        expect(await upgraded.forUser(1).pendingOperations(), isEmpty);
        expect(await upgraded.forUser(2).pendingOperations(), isEmpty);
        expect(await legacyMedia.exists(), isFalse);
        expect(await retainedAccountMedia.exists(), isTrue);
      } finally {
        await upgraded.close();
        await directory.delete(recursive: true);
      }
    },
  );

  test(
    'sync cleanup never removes a file outside the account outbox',
    () async {
      final directory = await Directory.systemTemp.createTemp(
        'agrishield-safe-cleanup-',
      );
      final database = await OfflineDatabase.open(
        factory: databaseFactoryFfi,
        databasePath: path.join(directory.path, 'offline.db'),
      );
      try {
        final unrelated = File(path.join(directory.path, 'unrelated.jpg'));
        await unrelated.writeAsBytes([1, 2, 3]);
        final account = database.forUser(1);
        await account.enqueue(
          localId: 'operation-1',
          entityType: 'diagnosis',
          operation: 'create',
          payload: {'farm_id': 'farm-a'},
          mediaPath: unrelated.path,
        );
        await account.markSynced('operation-1', serverId: 'server-id');
        final api = _OfflineApiClient()..setToken('token-a', ownerUserId: 1);

        await OutboxSyncService(
          api,
          account,
          documentsDirectory: () async => directory,
        ).synchronize();

        expect(await unrelated.exists(), isTrue);
        expect(
          (await account.operationById('operation-1'))!.mediaPath,
          unrelated.path,
        );
      } finally {
        await database.close();
        await directory.delete(recursive: true);
      }
    },
  );
}
