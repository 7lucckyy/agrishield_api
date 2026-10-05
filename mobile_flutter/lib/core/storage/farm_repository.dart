import '../../models/models.dart';
import '../api/api_client.dart';
import 'offline_database.dart';

class FarmRepositoryResult {
  const FarmRepositoryResult({
    required this.farms,
    required this.updatedAt,
    required this.isOffline,
  });

  final List<Farm> farms;
  final DateTime updatedAt;
  final bool isOffline;
}

class FarmDetailRepositoryResult {
  const FarmDetailRepositoryResult({
    required this.farm,
    required this.updatedAt,
    required this.isOffline,
  });

  final Farm farm;
  final DateTime updatedAt;
  final bool isOffline;
}

class FarmRepository {
  const FarmRepository(this._api, this._database);

  static const _collectionKey = 'farms';
  final ApiClient _api;
  final AccountOfflineStore _database;

  Future<FarmRepositoryResult> list() async {
    _ensureActiveOwner();
    try {
      final farms = await _api.farms();
      _ensureActiveOwner();
      final updatedAt = DateTime.now().toUtc();
      await _database.replaceCollection(
        _collectionKey,
        farms.map((farm) => farm.toJson()),
        updatedAt: updatedAt,
      );
      return FarmRepositoryResult(
        farms: farms,
        updatedAt: updatedAt,
        isOffline: false,
      );
    } on ApiException {
      _ensureActiveOwner();
      final cached = await _database.collection(_collectionKey);
      if (cached == null) rethrow;
      _ensureActiveOwner();
      return FarmRepositoryResult(
        farms: cached.items.map(Farm.fromJson).toList(),
        updatedAt: cached.updatedAt,
        isOffline: true,
      );
    }
  }

  Future<FarmDetailRepositoryResult> detail(String farmId) async {
    _ensureActiveOwner();
    final collectionKey = 'farm_detail:$farmId';
    try {
      final farm = await _api.farm(farmId);
      _ensureActiveOwner();
      if (farm.id != farmId) {
        throw const ApiException(
          'The requested farm did not match the response.',
        );
      }
      final updatedAt = DateTime.now().toUtc();
      await _database.replaceCollection(collectionKey, [
        farm.toJson(),
      ], updatedAt: updatedAt);
      return FarmDetailRepositoryResult(
        farm: farm,
        updatedAt: updatedAt,
        isOffline: false,
      );
    } on ApiException catch (error) {
      if (error.statusCode != null) rethrow;
      _ensureActiveOwner();
      final cached = await _database.collection(collectionKey);
      if (cached == null || cached.items.isEmpty) rethrow;
      _ensureActiveOwner();
      return FarmDetailRepositoryResult(
        farm: Farm.fromJson(cached.items.single),
        updatedAt: cached.updatedAt,
        isOffline: true,
      );
    }
  }

  void _ensureActiveOwner() {
    if (_api.authenticatedUserId != _database.userId) {
      throw const ApiException(
        'Sign in to the account that owns these saved farms.',
      );
    }
  }
}
