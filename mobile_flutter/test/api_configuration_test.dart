import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/api/api_logger.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('release builds only accept an HTTPS API URL', () {
    expect(
      () => configuredApiBaseUrl(
        endpoint: 'http://agrishield.ng/api/v1',
        release: true,
      ),
      throwsStateError,
    );
    expect(
      () => configuredApiBaseUrl(endpoint: 'not a url', release: true),
      throwsStateError,
    );
    expect(
      configuredApiBaseUrl(
        endpoint: 'https://api.example.test/api/v1',
        release: true,
      ),
      'https://api.example.test/api/v1',
    );
  });

  test('an empty API URL falls back to the production API', () {
    expect(
      configuredApiBaseUrl(endpoint: '', release: true),
      defaultApiBaseUrl,
    );
    expect(
      configuredApiBaseUrl(endpoint: '  ', release: false),
      defaultApiBaseUrl,
    );
  });

  test('debug builds may point at a local http API', () {
    expect(
      configuredApiBaseUrl(
        endpoint: 'http://10.0.2.2:8000/api/v1',
        release: false,
      ),
      'http://10.0.2.2:8000/api/v1',
    );
  });

  test('builds default to the AgriShield production API', () {
    expect(configuredApiBaseUrl(), defaultApiBaseUrl);
    expect(defaultApiBaseUrl, 'https://agrishield.ng/api/v1');
  });

  test('API logging shows calls but redacts tokens and passwords', () {
    final logs = <String>[];
    final interceptor = ApiLogInterceptor(logPrint: logs.add);
    final options = RequestOptions(
      baseUrl: defaultApiBaseUrl,
      path: '/auth/login',
      method: 'POST',
      headers: {'Authorization': 'Bearer secret-token'},
      data: {'phone': '+2348000000000', 'password': 'hunter22'},
    );

    interceptor.onRequest(options, RequestInterceptorHandler());
    interceptor.onResponse(
      Response(
        requestOptions: options,
        statusCode: 200,
        data: {
          'data': {
            'token': 'issued-token',
            'user': {'id': 1},
          },
        },
      ),
      ResponseInterceptorHandler(),
    );

    final output = logs.join('\n');
    expect(output, contains('→ POST https://agrishield.ng/api/v1/auth/login'));
    expect(output, contains('← 200 POST'));
    expect(output, contains('+2348000000000'));
    expect(output, isNot(contains('hunter22')));
    expect(output, isNot(contains('secret-token')));
    expect(output, isNot(contains('issued-token')));
  });

  test('debug API logging can show tokens but never passwords', () {
    final logs = <String>[];
    final interceptor = ApiLogInterceptor(logPrint: logs.add, showTokens: true);
    final options = RequestOptions(
      baseUrl: defaultApiBaseUrl,
      path: '/auth/login',
      method: 'POST',
      headers: {'Authorization': 'Bearer secret-token'},
      data: {'phone': '+2348000000000', 'password': 'hunter22'},
    );

    interceptor.onRequest(options, RequestInterceptorHandler());
    interceptor.onResponse(
      Response(
        requestOptions: options,
        statusCode: 200,
        data: {
          'data': {'token': 'issued-token'},
        },
      ),
      ResponseInterceptorHandler(),
    );

    final output = logs.join('\n');
    expect(output, contains('Bearer secret-token'));
    expect(output, contains('issued-token'));
    expect(output, isNot(contains('hunter22')));
  });

  test('API logging keeps validation messages readable', () {
    final logs = <String>[];
    final options = RequestOptions(
      baseUrl: defaultApiBaseUrl,
      path: '/auth/register',
      method: 'POST',
    );

    ApiLogInterceptor(logPrint: logs.add).onError(
      DioException(
        requestOptions: options,
        response: Response(
          requestOptions: options,
          statusCode: 422,
          data: {
            'message': 'Validation failed.',
            'errors': {
              'password': [
                'The password field must contain at least one number.',
              ],
            },
          },
        ),
      ),
      _ForwardingIgnoredErrorHandler(),
    );

    expect(
      logs.single,
      contains('The password field must contain at least one number.'),
    );
  });
}

/// Swallows the forwarded error so the interceptor can be tested in isolation.
class _ForwardingIgnoredErrorHandler extends ErrorInterceptorHandler {
  @override
  void next(DioException error) {}
}
