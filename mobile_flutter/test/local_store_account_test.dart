import 'package:agrishield_ai/core/storage/local_store.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStorage.setMockInitialValues({});
  });

  test(
    'token and profile are stored and cleared as one owned session',
    () async {
      final store = await LocalStore.create();
      await store.saveSession('token-a', {'id': 1, 'name': 'Farmer A'});
      final session = await store.readSession();
      expect(session?.token, 'token-a');
      expect(session?.ownerUserId, 1);
      expect(session?.profile['name'], 'Farmer A');

      await store.clearSession();
      expect(await store.readSession(), isNull);
    },
  );

  test('a mismatched stored owner and profile cannot restore', () async {
    final store = await LocalStore.create();
    await const FlutterSecureStorage().write(
      key: 'agrishield.auth.session.v2',
      value: '{"token":"token-b","owner_user_id":2,"profile":{"id":1}}',
    );
    expect(await store.readSession(), isNull);
  });

  test('organization selection and drafts stay with their owner', () async {
    final store = await LocalStore.create();
    await store.saveActiveOrganizationId(1, 10);
    await store.saveDraft(1, 'farm', {'name': 'A farm'});
    expect(store.activeOrganizationId(2), isNull);
    expect(await store.readDraft(2, 'farm'), isNull);
    expect(store.activeOrganizationId(1), 10);
    expect((await store.readDraft(1, 'farm'))?['name'], 'A farm');
  });
}
