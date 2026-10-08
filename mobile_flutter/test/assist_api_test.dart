import 'dart:io';

import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'uploads a photo with the selected language to the deployed API',
    () async {
      final directory = await Directory.systemTemp.createTemp('crop-language-');
      final image = File('${directory.path}/leaf.jpg');
      await image.writeAsBytes([0xff, 0xd8, 0xff, 0xd9]);
      final dio = Dio(BaseOptions(baseUrl: defaultApiBaseUrl));
      String? requestedUrl;
      String? responseLanguage;
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (options, handler) {
            requestedUrl = options.uri.toString();
            responseLanguage = (options.data as FormData).fields
                .firstWhere((field) => field.key == 'response_language')
                .value;
            handler.resolve(
              Response(
                requestOptions: options,
                statusCode: 202,
                data: {
                  'data': {'id': 'diagnosis-1'},
                },
              ),
            );
          },
        ),
      );

      try {
        await ApiClient(dio: dio).submitDiagnosis(
          farmId: 'farm-1',
          imagePath: image.path,
          responseLanguage: 'ha',
        );

        expect(
          requestedUrl,
          'https://agrishield.ng/api/v1/farms/farm-1/diagnosis-requests',
        );
        expect(responseLanguage, 'ha');
      } finally {
        await directory.delete(recursive: true);
      }
    },
  );

  test(
    'loads completed crop and voice results from their API endpoints',
    () async {
      final requestedPaths = <String>[];
      final dio = Dio(BaseOptions(baseUrl: 'https://example.test/api/v1'));
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (options, handler) {
            requestedPaths.add(options.path);
            handler.resolve(
              Response(
                requestOptions: options,
                statusCode: 200,
                data: {
                  'data': options.path.contains('diagnosis-requests')
                      ? {
                          'id': 'diagnosis-1',
                          'status': 'completed',
                          'diagnosis': 'Possible leaf damage',
                          'recommendation': 'Inspect nearby leaves.',
                        }
                      : {
                          'id': 'voice-1',
                          'status': 'completed',
                          'source_language': 'ha',
                          'response_language': 'en',
                          'transcript': 'Ganye na rawaya',
                          'guidance': 'Check the soil moisture.',
                          'safety_note': 'Consult an extension worker.',
                        },
                },
              ),
            );
          },
        ),
      );
      final api = ApiClient(dio: dio);

      final diagnosis = await api.diagnosis('farm-1', 'diagnosis-1');
      final voice = await api.voiceRequest('voice-1');

      expect(requestedPaths, [
        '/farms/farm-1/diagnosis-requests/diagnosis-1',
        '/voice-assistance/voice-1',
      ]);
      expect(diagnosis.recommendation, 'Inspect nearby leaves.');
      expect(voice.safetyNote, 'Consult an extension worker.');
    },
  );
}
