import 'package:agrishield_ai/core/api/api_client.dart';
import 'package:agrishield_ai/core/api/api_logger.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('release API endpoint requires an explicit HTTPS environment URL', () {
    expect(
      () => configuredApiBaseUrl(
        endpoint: '',
        environment: 'production',
        release: true,
      ),
      throwsStateError,
    );
    expect(
      () => configuredApiBaseUrl(
        endpoint: 'http://example.test/api/v1',
        environment: 'production',
        release: true,
      ),
      throwsStateError,
    );
    expect(
      configuredApiBaseUrl(
        endpoint: 'https://api.example.test/api/v1',
        environment: 'staging',
        release: true,
      ),
      'https://api.example.test/api/v1',
    );
  });

  test('development without a URL cannot accidentally use production', () {
    expect(
      configuredApiBaseUrl(
        endpoint: '',
        environment: 'development',
        release: false,
      ),
      'http://localhost:8000/api/v1',
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
