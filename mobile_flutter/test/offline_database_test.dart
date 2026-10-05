import 'package:agrishield_ai/core/storage/offline_database.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

void main() {
  late OfflineDatabase database;
  late AccountOfflineStore account;

  setUpAll(sqfliteFfiInit);

  setUp(() async {
    database = await OfflineDatabase.open(
      factory: databaseFactoryFfi,
      databasePath: inMemoryDatabasePath,
    );
    account = database.forUser(1);
  });

  tearDown(() => database.close());

  test(
    'farm collections persist payloads with a freshness timestamp',
    () async {
      final updatedAt = DateTime.utc(2026, 10, 3, 12, 30);
      await account.replaceCollection('farms', [
        {'id': 'farm-b', 'name': 'South Farm'},
        {'id': 'farm-a', 'name': 'North Farm'},
      ], updatedAt: updatedAt);

      final cached = await account.collection('farms');

      expect(cached, isNotNull);
      expect(cached!.updatedAt, updatedAt);
      expect(cached.items.map((item) => item['id']), ['farm-a', 'farm-b']);
    },
  );

  test(
    'replacing a collection removes records no longer returned by server',
    () async {
      await account.replaceCollection('farms', [
        {'id': 'farm-a'},
        {'id': 'farm-b'},
      ]);
      await account.replaceCollection('farms', [
        {'id': 'farm-b'},
      ]);

      final cached = await account.collection('farms');
      expect(cached!.items, [
        {'id': 'farm-b'},
      ]);
    },
  );

  test('outbox retains operations and explicit retry states', () async {
    final operation = await account.enqueue(
      localId: 'operation-1',
      entityType: 'voice',
      operation: 'create',
      payload: {'source_language': 'ha', 'response_language': 'ha'},
      mediaPath: '/private/voice.m4a',
      createdAt: DateTime.utc(2026, 10, 3),
    );

    expect(operation.status, SyncState.pending);
    expect((await account.pendingOperations()).single.localId, 'operation-1');

    await account.markSyncing(
      operation.localId,
      attemptedAt: DateTime.now().toUtc(),
    );
    expect(
      (await account.operationById(operation.localId))!.status,
      SyncState.syncing,
    );

    await account.markFailed(operation.localId, 'No connection');
    final failed = (await account.operationById(operation.localId))!;
    expect(failed.status, SyncState.failed);
    expect(failed.retryCount, 1);
    expect(failed.error, 'No connection');
    expect(await account.pendingOperations(), isEmpty);
    expect((await account.recentOperations()).single.status, SyncState.failed);

    await account.retry(operation.localId);
    expect(
      (await account.operationById(operation.localId))!.status,
      SyncState.pending,
    );

    await account.markSynced(operation.localId, serverId: 'server-12');
    final synced = (await account.operationById(operation.localId))!;
    expect(synced.status, SyncState.synced);
    expect(synced.serverId, 'server-12');
    expect(synced.mediaPath, '/private/voice.m4a');
    expect(await account.syncedMediaOperations(), hasLength(1));
    await account.clearSyncedMediaPath(operation.localId);
    expect((await account.operationById(operation.localId))!.mediaPath, isNull);
    expect(await account.pendingOperations(), isEmpty);
  });

  test('conflicts remain visible and are not retried automatically', () async {
    final operation = await account.enqueue(
      localId: 'operation-conflict',
      entityType: 'farm',
      operation: 'update',
      payload: {'name': 'Offline edit'},
    );

    await account.markConflict(operation.localId, 'Server record changed.');

    final conflict = (await account.operationById(operation.localId))!;
    expect(conflict.status, SyncState.conflict);
    expect(conflict.error, 'Server record changed.');
    expect(await account.pendingOperations(), isEmpty);
  });

  test(
    'an abandoned syncing operation becomes eligible without changing its key',
    () async {
      final operation = await account.enqueue(
        localId: 'stable-request-id',
        entityType: 'diagnosis',
        operation: 'create',
        payload: {'farm_id': 'farm-1'},
      );
      final oldAttempt = DateTime.now().toUtc().subtract(
        const Duration(minutes: 6),
      );
      await account.markSyncing(operation.localId, attemptedAt: oldAttempt);

      expect(
        (await account.pendingOperations()).single.localId,
        operation.localId,
      );
      expect(await account.claimForSync(operation.localId), isTrue);
      expect(await account.claimForSync(operation.localId), isFalse);
      expect(await account.pendingOperations(), isEmpty);
    },
  );
}
