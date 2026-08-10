<?php

use Illuminate\Support\Str;

test('validation errors use the API error envelope and preserve a valid request id', function () {
    $requestId = (string) Str::ulid();

    $response = $this->withHeader('X-Request-Id', $requestId)
        ->postJson('/api/v1/auth/register', []);

    $response
        ->assertUnprocessable()
        ->assertHeader('X-Request-Id', $requestId)
        ->assertJsonPath('message', 'Validation failed.')
        ->assertJsonPath('error_code', 'validation_failed')
        ->assertJsonPath('request_id', $requestId)
        ->assertJsonStructure(['errors']);
});

test('protected endpoints use the unauthenticated error envelope', function () {
    $response = $this->getJson('/api/v1/me');

    $response
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'unauthenticated')
        ->assertJsonStructure(['message', 'error_code', 'request_id']);
});

test('unknown API routes use the not found error envelope', function () {
    $response = $this->getJson('/api/v1/not-a-real-route');

    $response
        ->assertNotFound()
        ->assertJsonPath('error_code', 'not_found')
        ->assertJsonStructure(['message', 'error_code', 'request_id']);
});

test('authentication endpoints are rate limited by IP address', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => "unknown{$attempt}@example.com",
            'password' => 'incorrect-password',
        ])->assertUnauthorized();
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'limited@example.com',
        'password' => 'incorrect-password',
    ]);

    $response
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('error_code', 'rate_limited')
        ->assertJsonStructure(['message', 'error_code', 'request_id']);
});
