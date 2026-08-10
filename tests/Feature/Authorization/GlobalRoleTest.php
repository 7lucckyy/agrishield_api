<?php

use App\Enums\GlobalRole;
use App\Models\User;
use Database\Seeders\GlobalRoleSeeder;
use Spatie\Permission\Models\Role;

test('the global role seeder creates the platform roles idempotently', function () {
    $this->seed(GlobalRoleSeeder::class);
    $this->seed(GlobalRoleSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())->toBe([
        GlobalRole::Agronomist->value,
        GlobalRole::PlatformAdmin->value,
    ]);
});

test('global roles are returned by the profile endpoint', function () {
    $this->seed(GlobalRoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(GlobalRole::PlatformAdmin->value);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertSuccessful()
        ->assertJsonPath('data.roles.0', GlobalRole::PlatformAdmin->value);

    expect($user->hasRole(GlobalRole::PlatformAdmin->value))->toBeTrue();
});

test('registration never grants a global role', function () {
    $this->seed(GlobalRoleSeeder::class);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Independent Farmer',
        'email' => 'farmer@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.roles', []);

    expect(User::query()->sole()->roles)->toBeEmpty();
});
