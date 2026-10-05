import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SavedSession {
  const SavedSession({
    required this.token,
    required this.ownerUserId,
    required this.profile,
  });

  final String token;
  final int ownerUserId;
  final Map<String, dynamic> profile;
}

class LocalStore {
  LocalStore(this._preferences);
  static const _secureStorage = FlutterSecureStorage();
  static const _sessionKey = 'agrishield.auth.session.v2';
  final SharedPreferences _preferences;

  static Future<LocalStore> create() async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove('agrishield.cache.profile');
    await preferences.remove('agrishield.active_organization');
    await _secureStorage.delete(key: 'agrishield.auth.token');
    await _secureStorage.delete(key: 'agrishield.auth.profile');
    return LocalStore(preferences);
  }

  Future<SavedSession?> readSession() async {
    try {
      final value = await _secureStorage
          .read(key: _sessionKey)
          .timeout(const Duration(seconds: 4));
      if (value == null) return null;
      final decoded = jsonDecode(value);
      if (decoded is! Map) return null;
      final token = decoded['token'];
      final ownerUserId = decoded['owner_user_id'];
      final profile = decoded['profile'];
      if (token is! String ||
          token.isEmpty ||
          ownerUserId is! int ||
          ownerUserId <= 0 ||
          profile is! Map ||
          profile['id'] != ownerUserId) {
        return null;
      }
      return SavedSession(
        token: token,
        ownerUserId: ownerUserId,
        profile: Map<String, dynamic>.from(profile),
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> saveSession(String token, Map<String, dynamic> profile) {
    final ownerUserId = profile['id'];
    if (token.isEmpty || ownerUserId is! int || ownerUserId <= 0) {
      throw ArgumentError('A token and verified user ID are required.');
    }
    return _secureStorage.write(
      key: _sessionKey,
      value: jsonEncode({
        'token': token,
        'owner_user_id': ownerUserId,
        'profile': profile,
      }),
    );
  }

  Future<void> clearSession() => _secureStorage.delete(key: _sessionKey);

  int? activeOrganizationId(int ownerUserId) =>
      _preferences.getInt('agrishield.organization.$ownerUserId');
  Future<void> saveActiveOrganizationId(int ownerUserId, int id) =>
      _preferences.setInt('agrishield.organization.$ownerUserId', id);
  Future<void> saveDraft(
    int ownerUserId,
    String key,
    Map<String, dynamic> value,
  ) => _secureStorage.write(
    key: 'draft.$ownerUserId.$key',
    value: jsonEncode(value),
  );
  Future<Map<String, dynamic>?> readDraft(int ownerUserId, String key) async {
    final value = await _secureStorage.read(key: 'draft.$ownerUserId.$key');
    if (value == null) return null;
    try {
      return Map<String, dynamic>.from(jsonDecode(value) as Map);
    } catch (_) {
      return null;
    }
  }

  Future<void> clearDraft(int ownerUserId, String key) =>
      _secureStorage.delete(key: 'draft.$ownerUserId.$key');
}
