import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api/api_client.dart';
import '../core/storage/local_store.dart';
import '../models/models.dart';

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());
final localStoreProvider = FutureProvider<LocalStore>(
  (ref) => LocalStore.create(),
);
final connectivityProvider = StreamProvider<List<ConnectivityResult>>(
  (ref) => Connectivity().onConnectivityChanged,
);

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
  @override
  Future<AuthState> build() async {
    final store = await ref.read(localStoreProvider.future);
    final token = await store.readToken();
    if (token == null) return const AuthState(isRestoring: false);
    _api.setToken(token);
    try {
      final user = await _api.me();
      await store.cacheProfile(user.toJson());
      final selectedId = store.activeOrganizationId();
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
        await store.clearToken();
        return const AuthState(isRestoring: false);
      }
      final cached = store.cachedProfile();
      if (cached != null) {
        final user = UserProfile.fromJson(cached);
        final selectedId = store.activeOrganizationId();
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
      return const AuthState(isRestoring: false);
    }
  }

  Future<void> signIn(String phone, String password) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final session = await _api.login(phone, password);
      _api.setToken(session.token);
      final store = await ref.read(localStoreProvider.future);
      await store.saveToken(session.token);
      await store.cacheProfile(session.user.toJson());
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
      final session = await _api.register(payload);
      _api.setToken(session.token);
      final store = await ref.read(localStoreProvider.future);
      await store.saveToken(session.token);
      await store.cacheProfile(session.user.toJson());
      return AuthState(
        user: session.user,
        organizations: session.organizations,
        activeOrganization: session.organizations.firstOrNull,
        isRestoring: false,
      );
    });
  }

  Future<void> signOut() async {
    try {
      await _api.logout();
    } catch (_) {}
    _api.setToken(null);
    await (await ref.read(localStoreProvider.future)).clearToken();
    state = const AsyncData(AuthState(isRestoring: false));
  }

  void selectOrganization(Organization organization) {
    state = AsyncData(
      (state.value ?? const AuthState()).copyWith(
        activeOrganization: organization,
      ),
    );
    ref
        .read(localStoreProvider.future)
        .then((store) => store.saveActiveOrganizationId(organization.id));
  }
}

final authControllerProvider = AsyncNotifierProvider<AuthController, AuthState>(
  AuthController.new,
);

final farmsProvider = FutureProvider.autoDispose<List<Farm>>((ref) async {
  final api = ref.read(apiClientProvider);
  final store = await ref.read(localStoreProvider.future);
  try {
    final farms = await api.farms();
    await store.cacheFarms(farms.map((farm) => farm.toJson()).toList());
    return farms;
  } catch (_) {
    final cached = store.cachedFarms().map(Farm.fromJson).toList();
    if (cached.isNotEmpty) return cached;
    rethrow;
  }
});
