import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LocalStore {
  LocalStore(this._preferences);
  static const _secureStorage = FlutterSecureStorage();
  static const _tokenKey = 'agrishield.auth.token';
  static const _farmCacheKey = 'agrishield.cache.farms';
  static const _profileCacheKey = 'agrishield.cache.profile';
  static const _organizationKey = 'agrishield.active_organization';
  final SharedPreferences _preferences;

  static Future<LocalStore> create() async =>
      LocalStore(await SharedPreferences.getInstance());
  Future<String?> readToken() async {
    try {
      return await _secureStorage
          .read(key: _tokenKey)
          .timeout(const Duration(seconds: 4));
    } catch (_) {
      return null;
    }
  }

  Future<void> saveToken(String token) =>
      _secureStorage.write(key: _tokenKey, value: token);
  Future<void> clearToken() => _secureStorage.delete(key: _tokenKey);
  Future<void> cacheProfile(Map<String, dynamic> profile) async =>
      _preferences.setString(_profileCacheKey, jsonEncode(profile));
  Map<String, dynamic>? cachedProfile() {
    final value = _preferences.getString(_profileCacheKey);
    if (value == null) return null;
    try {
      return Map<String, dynamic>.from(jsonDecode(value) as Map);
    } catch (_) {
      return null;
    }
  }

  int? activeOrganizationId() => _preferences.getInt(_organizationKey);
  Future<void> saveActiveOrganizationId(int id) =>
      _preferences.setInt(_organizationKey, id);
  Future<void> cacheFarms(List<Map<String, dynamic>> farms) async =>
      _preferences.setString(_farmCacheKey, jsonEncode(farms));
  List<Map<String, dynamic>> cachedFarms() {
    final value = _preferences.getString(_farmCacheKey);
    if (value == null) return [];
    try {
      return (jsonDecode(value) as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    } catch (_) {
      return [];
    }
  }

  Future<void> saveDraft(String key, Map<String, dynamic> value) =>
      _preferences.setString('draft.$key', jsonEncode(value));
  Map<String, dynamic>? readDraft(String key) {
    final value = _preferences.getString('draft.$key');
    if (value == null) return null;
    try {
      return Map<String, dynamic>.from(jsonDecode(value) as Map);
    } catch (_) {
      return null;
    }
  }

  Future<void> clearDraft(String key) => _preferences.remove('draft.$key');
}
