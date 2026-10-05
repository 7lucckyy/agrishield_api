import 'package:agrishield_ai/core/api/api_client.dart';
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
}
