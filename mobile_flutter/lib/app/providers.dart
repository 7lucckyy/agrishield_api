import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api/api_client.dart';
import '../core/storage/farm_repository.dart';
import '../core/storage/local_store.dart';
import '../core/storage/offline_database.dart';
import '../core/storage/offline_submission_repository.dart';
import '../models/models.dart';

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());
final localStoreProvider = FutureProvider<LocalStore>(
  (ref) => LocalStore.create(),
);
final offlineDatabaseProvider = FutureProvider<OfflineDatabase>(
  (ref) => OfflineDatabase.open(),
);
final farmRepositoryProvider = FutureProvider<FarmRepository>((ref) async {
  final ownerUserId = ref.watch(authControllerProvider).value?.user?.id;
  if (ownerUserId == null) {
    throw StateError('Sign in before opening saved farms.');
  }
  return FarmRepository(
    ref.read(apiClientProvider),
    (await ref.read(offlineDatabaseProvider.future)).forUser(ownerUserId),
  );
});
final offlineSubmissionRepositoryProvider =
    FutureProvider<OfflineSubmissionRepository>((ref) async {
      final ownerUserId = ref.watch(authControllerProvider).value?.user?.id;
      if (ownerUserId == null) {
        throw StateError('Sign in before saving a request.');
      }
      return OfflineSubmissionRepository(
        ref.read(apiClientProvider),
        (await ref.read(offlineDatabaseProvider.future)).forUser(ownerUserId),
      );
    });
final connectivityProvider = StreamProvider<List<ConnectivityResult>>(
  (ref) => Connectivity().onConnectivityChanged,
);
final outboxSyncProvider = FutureProvider<void>((ref) async {
  final ownerUserId = ref.watch(authControllerProvider).value?.user?.id;
  if (ownerUserId == null) return;
  final connectivity = ref.watch(connectivityProvider).value;
  final isOnline = connectivity?.any(
    (result) => result != ConnectivityResult.none,
  );
  if (isOnline != true) return;
  final database = await ref.read(offlineDatabaseProvider.future);
  await OutboxSyncService(
    ref.read(apiClientProvider),
    database.forUser(ownerUserId),
  ).synchronize();
});
final outboxStatusProvider = FutureProvider.autoDispose<List<OutboxOperation>>((
  ref,
) async {
  final ownerUserId = ref.watch(authControllerProvider).value?.user?.id;
  if (ownerUserId == null) return [];
  await ref.watch(outboxSyncProvider.future);
  final database = await ref.read(offlineDatabaseProvider.future);
  return database.forUser(ownerUserId).recentOperations();
});

class AuthState {
  const AuthState({
    this.user,
    this.organizations = const [],
    this.activeOrganization,
    this.isRestoring = true,
  });
  final UserProfile? user;
  final List<Organization> organizations;
  final Organization? activeOrganization;
  final bool isRestoring;
  bool get isAuthenticated => user != null;
  AuthState copyWith({
    UserProfile? user,
    List<Organization>? organizations,
    Organization? activeOrganization,
    bool? isRestoring,
    bool clearUser = false,
  }) => AuthState(
    user: clearUser ? null : user ?? this.user,
    organizations: organizations ?? this.organizations,
    activeOrganization: activeOrganization ?? this.activeOrganization,
    isRestoring: isRestoring ?? this.isRestoring,
  );
}

class AuthController extends AsyncNotifier<AuthState> {
  ApiClient get _api => ref.read(apiClientProvider);
  Future<void>? _signOutInProgress;
  @override
  Future<AuthState> build() async {
    final store = await ref.read(localStoreProvider.future);
    final session = await store.readSession();
    if (session == null) return const AuthState(isRestoring: false);
    _api.setToken(session.token);
    try {
      final user = await _api.me();
      if (user.id != session.ownerUserId) {
        _api.setToken(null);
        await store.clearSession();
        return const AuthState(isRestoring: false);
      }
      _api.setToken(session.token, ownerUserId: user.id);
      await store.saveSession(session.token, user.toJson());
      final selectedId = store.activeOrganizationId(user.id);
      return AuthState(
        user: user,
        organizations: user.organizations,
        activeOrganization:
            user.organizations
                .where((organization) => organization.id == selectedId)
                .firstOrNull ??
            user.organizations.firstOrNull,
        isRestoring: false,
      );
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        _api.setToken(null);
        await store.clearSession();
        return const AuthState(isRestoring: false);
      }
      final user = UserProfile.fromJson(session.profile);
      _api.setToken(session.token, ownerUserId: user.id);
      final selectedId = store.activeOrganizationId(user.id);
      return AuthState(
        user: user,
        organizations: user.organizations,
        activeOrganization:
            user.organizations
                .where((organization) => organization.id == selectedId)
                .firstOrNull ??
            user.organizations.firstOrNull,
        isRestoring: false,
      );
    }
  }

  Future<void> signIn(String phone, String password) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      await _signOutInProgress;
      final session = await _api.login(phone, password);
      _api.setToken(session.token, ownerUserId: session.user.id);
      final store = await ref.read(localStoreProvider.future);
      await store.saveSession(session.token, session.user.toJson());
      return AuthState(
        user: session.user,
        organizations: session.organizations,
        activeOrganization: session.organizations.firstOrNull,
        isRestoring: false,
      );
    });
  }

  Future<void> register(Json payload) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      await _signOutInProgress;
      final session = await _api.register(payload);
      _api.setToken(session.token, ownerUserId: session.user.id);
      final store = await ref.read(localStoreProvider.future);
      await store.saveSession(session.token, session.user.toJson());
      return AuthState(
        user: session.user,
        organizations: session.organizations,
        activeOrganization: session.organizations.firstOrNull,
        isRestoring: false,
      );
    });
  }

  Future<void> signOut() async {
    if (_signOutInProgress != null) {
      await _signOutInProgress;
      return;
    }
    _api.suspendOfflineSync();
    state = const AsyncData(AuthState(isRestoring: false));
    _signOutInProgress = _finishSignOut();
    try {
      await _signOutInProgress;
    } finally {
      _signOutInProgress = null;
    }
  }

  Future<void> _finishSignOut() async {
    try {
      await _api.logout();
    } catch (_) {
      // Local sign-out must still succeed when the device is offline.
    } finally {
      _api.setToken(null);
      final store = await ref.read(localStoreProvider.future);
      await store.clearSession();
    }
  }

  void selectOrganization(Organization organization) {
    final ownerUserId = state.value?.user?.id;
    if (ownerUserId == null) return;
    state = AsyncData(
      (state.value ?? const AuthState()).copyWith(
        activeOrganization: organization,
      ),
    );
    ref
        .read(localStoreProvider.future)
        .then(
          (store) =>
              store.saveActiveOrganizationId(ownerUserId, organization.id),
        );
  }
}

final authControllerProvider = AsyncNotifierProvider<AuthController, AuthState>(
  AuthController.new,
);

final farmsProvider = FutureProvider.autoDispose<List<Farm>>((ref) async {
  final repository = await ref.read(farmRepositoryProvider.future);
  return (await repository.list()).farms;
});
