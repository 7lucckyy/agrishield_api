<?php

use App\Enums\UserStatus;
use App\Models\User;

test('a user can log in with an email address', function () {
    $user = User::factory()->create([
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'AMINA@example.com',
        'password' => 'correct-horse-battery-staple',
        'device_name' => 'Amina phone',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.user.id', $user->getKey())
        ->assertJsonPath('data.token_type', 'Bearer');

    expect($user->fresh()->last_login_at)->not->toBeNull();
    expect($user->tokens()->value('name'))->toBe('Amina phone');
    expect($user->tokens()->value('expires_at'))->toBeNull();
});

test('a farmer can log in with a phone number', function () {
    $user = User::factory()->create([
        'email' => null,
        'phone' => '+2348012345678',
        'password' => 'correct-horse-battery-staple',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'phone' => '+2348012345678',
        'password' => 'correct-horse-battery-staple',
        'device_name' => 'Musa phone',
    ])->assertSuccessful()->assertJsonPath('data.user.id', $user->getKey());
});

test('unknown users and wrong passwords return the same error', function (array $credentials) {
    User::factory()->create([
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);

    $response = $this->postJson('/api/v1/auth/login', $credentials);

    $response
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'invalid_credentials')
        ->assertJsonStructure(['message', 'error_code', 'request_id']);
})->with([
    'unknown user' => [[
        'email' => 'unknown@example.com',
        'password' => 'correct-horse-battery-staple',
    ]],
    'wrong password' => [[
        'email' => 'amina@example.com',
        'password' => 'incorrect-password',
    ]],
]);

test('a suspended user cannot log in and existing tokens are revoked', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Suspended,
        'password' => 'correct-horse-battery-staple',
    ]);
    $user->createToken('old phone');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-horse-battery-staple',
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('error_code', 'account_suspended');

    expect($user->tokens()->count())->toBe(0);
});

test('logout revokes only the presenting token', function () {
    $user = User::factory()->create();
    $presentingToken = $user->createToken('presenting');
    $otherToken = $user->createToken('other');

    $this->withToken($presentingToken->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->tokens()->pluck('id')->all())
        ->toBe([$otherToken->accessToken->getKey()]);
});

test('revoke all removes every token', function () {
    $user = User::factory()->create();
    $presentingToken = $user->createToken('presenting');
    $user->createToken('other');

    $this->withToken($presentingToken->plainTextToken)
        ->postJson('/api/v1/auth/tokens/revoke-all')
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
