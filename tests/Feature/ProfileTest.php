<?php

use App\Models\User;

test('an authenticated user can view their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->getKey())
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure(['data' => ['roles', 'organizations']]);
});

test('an authenticated user can update allowed profile fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/me', [
            'name' => 'Updated Farmer',
            'phone' => '+2348099999999',
            'locale' => 'en',
            'status' => 'suspended',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Farmer')
        ->assertJsonPath('data.status', 'active');

    expect($user->fresh())
        ->name->toBe('Updated Farmer')
        ->phone->toBe('+2348099999999');
});

test('changing a password revokes every other token', function () {
    $user = User::factory()->create([
        'password' => 'current-password',
    ]);
    $presentingToken = $user->createToken('presenting');
    $user->createToken('other');

    $this->withToken($presentingToken->plainTextToken)
        ->patchJson('/api/v1/me/password', [
            'current_password' => 'current-password',
            'password' => 'new-correct-horse-battery-staple',
            'password_confirmation' => 'new-correct-horse-battery-staple',
        ])
        ->assertNoContent();

    expect($user->tokens()->pluck('id')->all())
        ->toBe([$presentingToken->accessToken->getKey()]);
});
