import 'dart:convert';
import 'dart:io';

import 'package:path/path.dart' as path;
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

enum SyncState { pending, syncing, synced, failed, conflict }

class CachedCollection<T> {
  const CachedCollection({required this.items, required this.updatedAt});

  final List<T> items;
  final DateTime updatedAt;
}

class OutboxOperation {
  const OutboxOperation({
    required this.localId,
    required this.ownerUserId,
    required this.entityType,
    required this.operation,
    required this.payload,
    required this.createdAt,
    required this.updatedAt,
    required this.status,
    this.serverId,
    this.mediaPath,
    this.retryCount = 0,
    this.lastAttemptAt,
    this.error,
  });

  final String localId;
  final int ownerUserId;
  final String entityType;
  final String operation;
  final Map<String, dynamic> payload;
  final String? serverId;
  final String? mediaPath;
  final DateTime createdAt;
  final DateTime updatedAt;
  final int retryCount;
  final DateTime? lastAttemptAt;
  final SyncState status;
  final String? error;

  factory OutboxOperation.fromRow(Map<String, Object?> row) => OutboxOperation(
    localId: row['local_id']! as String,
    ownerUserId: row['owner_user_id']! as int,
    entityType: row['entity_type']! as String,
    operation: row['operation_type']! as String,
    payload: Map<String, dynamic>.from(
      jsonDecode(row['payload_json']! as String) as Map,
    ),
    serverId: row['server_id'] as String?,
    mediaPath: row['media_path'] as String?,
    createdAt: DateTime.parse(row['created_at']! as String),
    updatedAt: DateTime.parse(row['updated_at']! as String),
    retryCount: row['retry_count']! as int,
    lastAttemptAt: row['last_attempt_at'] == null
        ? null
        : DateTime.parse(row['last_attempt_at']! as String),
    status: SyncState.values.byName(row['status']! as String),
    error: row['error'] as String?,
  );
}

class OfflineDatabase {
  OfflineDatabase._(this._database);

  static const schemaVersion = 2;
  static const _databaseName = 'agrishield_offline.db';
  final Database _database;

  static Future<OfflineDatabase> open({
    DatabaseFactory? factory,
    String? databasePath,
    Future<Directory> Function()? documentsDirectory,
  }) async {
    var discardedLegacyRows = false;
    final selectedFactory = factory ?? databaseFactory;
    final selectedPath =
        databasePath ?? path.join(await getDatabasesPath(), _databaseName);
    final database = await selectedFactory.openDatabase(
      selectedPath,
      options: OpenDatabaseOptions(
        version: schemaVersion,
        onConfigure: (database) async {
          await database.execute('PRAGMA foreign_keys = ON');
        },
        onCreate: _createSchema,
        onUpgrade: (database, oldVersion, newVersion) async {
          if (oldVersion < 2) {
            discardedLegacyRows = true;
            // Version one never recorded an owner. Assigning its rows to the
            // current login could expose or transmit another account's data.
            await database.execute('DROP TABLE IF EXISTS cached_records');
            await database.execute('DROP TABLE IF EXISTS outbox_operations');
            await _createSchema(database, newVersion);
          }
        },
      ),
    );

    if (discardedLegacyRows) {
      await _removeUnownedLegacyMedia(
        documentsDirectory ?? getApplicationDocumentsDirectory,
      );
    }

    return OfflineDatabase._(database);
  }

  static Future<void> _removeUnownedLegacyMedia(
    Future<Directory> Function() documentsDirectory,
  ) async {
    final documents = await documentsDirectory();
    final legacyRoot = Directory(
      path.join(documents.path, 'agrishield_outbox'),
    );
    if (!await legacyRoot.exists()) return;

    final legacyFileName = RegExp(
      r'^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\.[A-Za-z0-9]+$',
    );
    await for (final entity in legacyRoot.list(followLinks: false)) {
      if (entity is File &&
          legacyFileName.hasMatch(path.basename(entity.path))) {
        await entity.delete();
      }
    }
  }

  AccountOfflineStore forUser(int userId) {
    if (userId <= 0) {
      throw ArgumentError.value(
        userId,
        'userId',
        'A verified user ID is required.',
      );
    }
    return AccountOfflineStore._(_database, userId);
  }

  static Future<void> _createSchema(Database database, int version) async {
    await database.execute('''
      CREATE TABLE cached_records (
        owner_user_id INTEGER NOT NULL,
        collection_key TEXT NOT NULL,
        record_key TEXT NOT NULL,
        payload_json TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        PRIMARY KEY (owner_user_id, collection_key, record_key)
      )
    ''');
    await database.execute('''
      CREATE INDEX idx_cached_records_collection_updated
      ON cached_records (owner_user_id, collection_key, updated_at)
    ''');
    await database.execute('''
      CREATE TABLE outbox_operations (
        local_id TEXT PRIMARY KEY,
        owner_user_id INTEGER NOT NULL,
        entity_type TEXT NOT NULL,
        server_id TEXT,
        operation_type TEXT NOT NULL,
        payload_json TEXT NOT NULL,
        media_path TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        retry_count INTEGER NOT NULL DEFAULT 0,
        last_attempt_at TEXT,
        status TEXT NOT NULL DEFAULT 'pending'
          CHECK (status IN ('pending', 'syncing', 'synced', 'failed', 'conflict')),
        error TEXT
      )
    ''');
    await database.execute('''
      CREATE INDEX idx_outbox_status_created
      ON outbox_operations (owner_user_id, status, created_at)
    ''');
  }

  Future<void> close() => _database.close();
}

class AccountOfflineStore {
  AccountOfflineStore._(this._database, this.userId);

  final Database _database;
  final int userId;

  Future<void> replaceCollection(
    String collectionKey,
    Iterable<Map<String, dynamic>> records, {
    DateTime? updatedAt,
  }) async {
    final timestamp = (updatedAt ?? DateTime.now().toUtc()).toIso8601String();
    await _database.transaction((transaction) async {
      await transaction.delete(
        'cached_records',
        where: 'owner_user_id = ? AND collection_key = ?',
        whereArgs: [userId, collectionKey],
      );
      final batch = transaction.batch();
      for (final record in records) {
        final recordKey = record['id']?.toString();
        if (recordKey == null || recordKey.isEmpty) {
          throw ArgumentError.value(
            record,
            'records',
            'Every record needs an id.',
          );
        }
        batch.insert('cached_records', {
          'owner_user_id': userId,
          'collection_key': collectionKey,
          'record_key': recordKey,
          'payload_json': jsonEncode(record),
          'updated_at': timestamp,
        });
      }
      await batch.commit(noResult: true);
    });
  }

  Future<CachedCollection<Map<String, dynamic>>?> collection(
    String collectionKey,
  ) async {
    final rows = await _database.query(
      'cached_records',
      where: 'owner_user_id = ? AND collection_key = ?',
      whereArgs: [userId, collectionKey],
      orderBy: 'record_key ASC',
    );
    if (rows.isEmpty) return null;

    return CachedCollection(
      items: rows
          .map(
            (row) => Map<String, dynamic>.from(
              jsonDecode(row['payload_json']! as String) as Map,
            ),
          )
          .toList(),
      updatedAt: DateTime.parse(rows.first['updated_at']! as String),
    );
  }

  Future<OutboxOperation> enqueue({
    required String entityType,
    required String operation,
    required Map<String, dynamic> payload,
    String? serverId,
    String? mediaPath,
    String? localId,
    DateTime? createdAt,
  }) async {
    final now = (createdAt ?? DateTime.now().toUtc()).toIso8601String();
    final id = localId ?? const Uuid().v4();
    await _database.insert('outbox_operations', {
      'local_id': id,
      'owner_user_id': userId,
      'entity_type': entityType,
      'server_id': serverId,
      'operation_type': operation,
      'payload_json': jsonEncode(payload),
      'media_path': mediaPath,
      'created_at': now,
      'updated_at': now,
      'status': SyncState.pending.name,
    });

    return (await operationById(id))!;
  }

  Future<List<OutboxOperation>> pendingOperations({int limit = 25}) async {
    final rows = await _database.query(
      'outbox_operations',
      where: 'owner_user_id = ? AND status IN (?, ?, ?)',
      whereArgs: [
        userId,
        SyncState.pending.name,
        SyncState.failed.name,
        SyncState.syncing.name,
      ],
      orderBy: 'created_at ASC',
      limit: limit * 4,
    );
    final now = DateTime.now().toUtc();
    return rows
        .map(OutboxOperation.fromRow)
        .where((operation) => _isRetryDue(operation, now))
        .take(limit)
        .toList();
  }

  bool _isRetryDue(OutboxOperation operation, DateTime now) {
    if (operation.status == SyncState.synced ||
        operation.status == SyncState.conflict) {
      return false;
    }
    if (operation.status == SyncState.syncing) {
      return operation.lastAttemptAt == null ||
          !operation.lastAttemptAt!
              .add(const Duration(minutes: 5))
              .isAfter(now);
    }
    if (operation.status == SyncState.pending ||
        operation.lastAttemptAt == null) {
      return true;
    }
    final exponent = operation.retryCount.clamp(0, 8);
    final delaySeconds = 30 * (1 << exponent);
    return !operation.lastAttemptAt!
        .add(Duration(seconds: delaySeconds))
        .isAfter(now);
  }

  Future<OutboxOperation?> operationById(String localId) async {
    final rows = await _database.query(
      'outbox_operations',
      where: 'owner_user_id = ? AND local_id = ?',
      whereArgs: [userId, localId],
      limit: 1,
    );
    return rows.isEmpty ? null : OutboxOperation.fromRow(rows.first);
  }

  Future<List<OutboxOperation>> recentOperations({int limit = 20}) async {
    final rows = await _database.query(
      'outbox_operations',
      where: 'owner_user_id = ?',
      whereArgs: [userId],
      orderBy: 'created_at DESC',
      limit: limit,
    );
    return rows.map(OutboxOperation.fromRow).toList();
  }

  Future<void> markSyncing(String localId, {DateTime? attemptedAt}) =>
      _updateOperation(localId, {
        'status': SyncState.syncing.name,
        'last_attempt_at': (attemptedAt ?? DateTime.now().toUtc())
            .toIso8601String(),
        'error': null,
      });

  Future<bool> claimForSync(String localId, {DateTime? attemptedAt}) async {
    final now = (attemptedAt ?? DateTime.now()).toUtc();
    return _database.transaction((transaction) async {
      final rows = await transaction.query(
        'outbox_operations',
        where: 'owner_user_id = ? AND local_id = ?',
        whereArgs: [userId, localId],
        limit: 1,
      );
      if (rows.isEmpty ||
          !_isRetryDue(OutboxOperation.fromRow(rows.first), now)) {
        return false;
      }
      await transaction.update(
        'outbox_operations',
        {
          'status': SyncState.syncing.name,
          'last_attempt_at': now.toIso8601String(),
          'updated_at': now.toIso8601String(),
          'error': null,
        },
        where: 'owner_user_id = ? AND local_id = ?',
        whereArgs: [userId, localId],
      );
      return true;
    });
  }

  Future<void> markSynced(String localId, {String? serverId}) =>
      _updateOperation(localId, {
        'status': SyncState.synced.name,
        'server_id': serverId,
        'error': null,
      });

  Future<List<OutboxOperation>> syncedMediaOperations() async {
    final rows = await _database.query(
      'outbox_operations',
      where: 'owner_user_id = ? AND status = ? AND media_path IS NOT NULL',
      whereArgs: [userId, SyncState.synced.name],
    );
    return rows.map(OutboxOperation.fromRow).toList();
  }

  Future<void> clearSyncedMediaPath(String localId) async {
    await _database.update(
      'outbox_operations',
      {
        'media_path': null,
        'updated_at': DateTime.now().toUtc().toIso8601String(),
      },
      where: 'owner_user_id = ? AND local_id = ? AND status = ?',
      whereArgs: [userId, localId, SyncState.synced.name],
    );
  }

  Future<void> markFailed(String localId, String error) async {
    final operation = await operationById(localId);
    if (operation == null) return;
    await _updateOperation(localId, {
      'status': SyncState.failed.name,
      'retry_count': operation.retryCount + 1,
      'error': error,
    });
  }

  Future<void> markConflict(String localId, String error) => _updateOperation(
    localId,
    {'status': SyncState.conflict.name, 'error': error},
  );

  Future<void> retry(String localId) => _updateOperation(localId, {
    'status': SyncState.pending.name,
    'error': null,
  });

  Future<void> _updateOperation(
    String localId,
    Map<String, Object?> values,
  ) async {
    await _database.update(
      'outbox_operations',
      {...values, 'updated_at': DateTime.now().toUtc().toIso8601String()},
      where: 'owner_user_id = ? AND local_id = ?',
      whereArgs: [userId, localId],
    );
  }
}
