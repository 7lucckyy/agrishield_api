<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

test('registration login profile and logout work end to end with bearer tokens', function () {
    $credentials = [
        'name' => 'Amina Bello',
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ];

    $registrationToken = $this->postJson('/api/v1/auth/register', $credentials)
        ->assertCreated()
        ->json('data.token');

    expect($registrationToken)->toBeString()->not->toBeEmpty();

    $loginToken = $this->postJson('/api/v1/auth/login', [
        'email' => $credentials['email'],
        'password' => $credentials['password'],
        'device_name' => 'Phase 1 acceptance test',
    ])
        ->assertSuccessful()
        ->json('data.token');

    expect($loginToken)->toBeString()->not->toBeEmpty();

    $this->withToken($loginToken)
        ->getJson('/api/v1/me')
        ->assertSuccessful()
        ->assertJsonPath('data.email', $credentials['email']);

    $this->withToken($loginToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    Auth::forgetGuards();

    $this->withToken($loginToken)
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'unauthenticated');

    Auth::forgetGuards();

    $this->withToken($registrationToken)
        ->getJson('/api/v1/me')
        ->assertSuccessful()
        ->assertJsonPath('data.email', $credentials['email']);

    expect(User::query()->sole()->tokens)->toHaveCount(1);
});
