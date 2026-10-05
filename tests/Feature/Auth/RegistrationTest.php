<?php

use App\Models\User;

test('a farmer registers with a phone number and receives a persistent device token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'phone' => '+2348012345678',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response
        ->assertCreated()
        ->assertHeader('X-Request-Id')
        ->assertJsonPath('data.user.email', null)
        ->assertJsonPath('data.user.phone', '+2348012345678')
        ->assertJsonPath('data.user.status', 'active')
        ->assertJsonPath('data.token_type', 'Bearer');

    $user = User::query()->sole();

    $this->assertModelExists($user);
    expect($user->password)->not->toBe('correct-horse-battery-staple');
    expect($user->tokens)->toHaveCount(1);
    expect($user->tokens()->sole()->expires_at)->toBeNull();
});

test('email registration is rejected because farmer identity is phone based', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone']);
});
