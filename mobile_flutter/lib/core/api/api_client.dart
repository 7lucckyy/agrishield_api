import 'dart:io';

import 'package:dio/dio.dart';
import 'package:uuid/uuid.dart';

import '../../models/models.dart';

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.errors = const {}});
  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;
  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({Dio? dio})
    : _dio =
          dio ??
          Dio(
            BaseOptions(
              baseUrl: const String.fromEnvironment(
                'API_BASE_URL',
                defaultValue: 'https://agrishield.ng/api/v1',
              ),
              connectTimeout: const Duration(seconds: 20),
              receiveTimeout: const Duration(seconds: 30),
              headers: const {'Accept': 'application/json'},
            ),
          ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onError: (error, handler) {
          final data = error.response?.data;
          final rawErrors = data is Map ? data['errors'] : null;
          final errors = <String, List<String>>{};
          if (rawErrors is Map) {
            for (final entry in rawErrors.entries) {
              errors[entry.key
                  .toString()] = (entry.value as List? ?? [entry.value])
                  .map((value) => value.toString())
                  .toList();
            }
          }
          handler.reject(
            DioException(
              requestOptions: error.requestOptions,
              response: error.response,
              type: error.type,
              error: ApiException(
                data is Map && data['message'] != null
                    ? data['message'].toString()
                    : 'The request could not be completed.',
                statusCode: error.response?.statusCode,
                errors: errors,
              ),
            ),
          );
        },
      ),
    );
  }

  final Dio _dio;
  void setToken(String? token) {
    if (token == null) {
      _dio.options.headers.remove('Authorization');
    } else {
      _dio.options.headers['Authorization'] = 'Bearer $token';
    }
  }

  Never _throw(Object error) {
    if (error is DioException && error.error is ApiException) {
      throw error.error! as ApiException;
    }
    throw const ApiException(
      'Unable to reach AgriShield. Check your connection and try again.',
    );
  }

  Future<AuthSession> login(String phone, String password) async {
    try {
      return AuthSession.fromJson(
        (await _dio.post(
              '/auth/login',
              data: {
                'phone': phone.trim(),
                'password': password,
                'device_name': 'AgriShield Flutter',
              },
            )).data['data']
            as Json,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<AuthSession> register(Json payload) async {
    try {
      return AuthSession.fromJson(
        (await _dio.post('/auth/register', data: payload)).data['data'] as Json,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<UserProfile> me() async {
    try {
      return UserProfile.fromJson((await _dio.get('/me')).data['data'] as Json);
    } catch (error) {
      _throw(error);
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<Farm>> farms({bool activeOnly = false}) async {
    try {
      final response = await _dio.get(
        '/farms',
        queryParameters: {
          if (activeOnly) 'filter[status]': 'active',
          'per_page': 50,
        },
      );
      return (response.data['data'] as List)
          .whereType<Json>()
          .map(Farm.fromJson)
          .toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<Farm> farm(String id) async {
    try {
      return Farm.fromJson((await _dio.get('/farms/$id')).data['data'] as Json);
    } catch (error) {
      _throw(error);
    }
  }

  Future<Farm> createFarm(Json payload) async {
    try {
      return Farm.fromJson(
        (await _dio.post('/farms', data: payload)).data['data'] as Json,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<Crop>> crops() async {
    try {
      final response = await _dio.get(
        '/crops',
        queryParameters: {'filter[active]': true, 'per_page': 100},
      );
      return (response.data['data'] as List)
          .whereType<Json>()
          .map(Crop.fromJson)
          .toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<FarmSection> createFarmSection(String farmId, Json payload) async {
    try {
      return FarmSection.fromJson(
        (await _dio.post('/farms/$farmId/sections', data: payload)).data['data']
            as Json,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<FarmSection> updateFarmSection(
    String farmId,
    int sectionId,
    Json payload,
  ) async {
    try {
      return FarmSection.fromJson(
        (await _dio.patch(
              '/farms/$farmId/sections/$sectionId',
              data: payload,
            )).data['data']
            as Json,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<void> deleteFarmSection(String farmId, int sectionId) async {
    try {
      await _dio.delete('/farms/$farmId/sections/$sectionId');
    } catch (error) {
      _throw(error);
    }
  }

  Future<void> syncFarm(String id) async {
    try {
      await _dio.post('/farms/$id/sync', data: <String, dynamic>{});
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<WeatherDay>> weather(String farmId) async {
    try {
      final days =
          (await _dio.get('/farms/$farmId/weather')).data['data']['days']
              as List;
      return days.whereType<Json>().map(WeatherDay.fromJson).toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<Advisory>> advisories(String farmId) async {
    try {
      final data =
          (await _dio.get(
                '/farms/$farmId/advisories',
                queryParameters: {'per_page': 10},
              )).data['data']
              as List;
      return data.whereType<Json>().map(Advisory.fromJson).toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<Json> soil(String farmId) async {
    try {
      return Map<String, dynamic>.from(
        (await _dio.get('/farms/$farmId/soil-health')).data['data'] as Map,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<Json> submitDiagnosis({
    required String farmId,
    required String imagePath,
    String? note,
  }) async {
    try {
      final file = File(imagePath);
      final form = FormData.fromMap({
        'note': note,
        'image': await MultipartFile.fromFile(
          imagePath,
          filename: file.uri.pathSegments.last,
        ),
      });
      return Map<String, dynamic>.from(
        (await _dio.post(
              '/farms/$farmId/diagnosis-requests',
              data: form,
              options: Options(headers: {'Idempotency-Key': const Uuid().v4()}),
            )).data['data']
            as Map,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<Diagnosis>> diagnoses(String farmId) async {
    try {
      final data =
          (await _dio.get(
                '/farms/$farmId/diagnosis-requests',
                queryParameters: {'per_page': 10},
              )).data['data']
              as List;
      return data.whereType<Json>().map(Diagnosis.fromJson).toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<Json> submitVoice({
    required String path,
    required String sourceLanguage,
    required String responseLanguage,
    String? farmId,
  }) async {
    try {
      final file = File(path);
      final form = FormData.fromMap({
        'source_language': sourceLanguage,
        'response_language': responseLanguage,
        'farm_id': farmId,
        'audio': await MultipartFile.fromFile(
          path,
          filename: file.uri.pathSegments.last,
        ),
      });
      return Map<String, dynamic>.from(
        (await _dio.post('/voice-assistance', data: form)).data['data'] as Map,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<VoiceRequest>> voiceRequests() async {
    try {
      final data =
          (await _dio.get(
                '/voice-assistance',
                queryParameters: {'per_page': 10},
              )).data['data']
              as List;
      return data.whereType<Json>().map(VoiceRequest.fromJson).toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<FinanceProduct>> financeProducts() async {
    try {
      final data =
          (await _dio.get('/asset-finance/products')).data['data'] as List;
      return data.whereType<Json>().map(FinanceProduct.fromJson).toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<Json> applyForFinance(Json payload) async {
    try {
      return Map<String, dynamic>.from(
        (await _dio.post(
              '/asset-finance/applications',
              data: payload,
            )).data['data']
            as Map,
      );
    } catch (error) {
      _throw(error);
    }
  }

  Future<List<FinanceApplication>> financeApplications() async {
    try {
      final data =
          (await _dio.get(
                '/asset-finance/applications',
                queryParameters: {'per_page': 20},
              )).data['data']
              as List;
      return data.whereType<Json>().map(FinanceApplication.fromJson).toList();
    } catch (error) {
      _throw(error);
    }
  }

  Future<bool> health() async {
    try {
      await _dio.get('/health');
      return true;
    } catch (_) {
      return false;
    }
  }
}
