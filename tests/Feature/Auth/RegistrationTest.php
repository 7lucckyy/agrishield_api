<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\ReferralRedemption;
use App\Models\User;

test('a farmer can register with an email address', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'email' => 'AMINA@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response
        ->assertCreated()
        ->assertHeader('X-Request-Id')
        ->assertJsonPath('data.user.email', 'amina@example.com')
        ->assertJsonPath('data.user.status', 'active')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('meta.referral_applied', false);

    $user = User::query()->sole();

    $this->assertModelExists($user);
    expect($user->password)->not->toBe('correct-horse-battery-staple');
    expect($user->tokens)->toHaveCount(1);
});

test('a farmer can register with a phone number only', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Musa Ibrahim',
        'phone' => '+2348012345678',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.email', null)
        ->assertJsonPath('data.user.phone', '+2348012345678');
});

test('a referral code binds a new user as a farmer and records redemption', function () {
    $organization = Organization::factory()->create([
        'referral_code' => 'KANO2026',
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
        'referral_code' => 'kano2026',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.organizations.0.id', $organization->getKey())
        ->assertJsonPath('data.organizations.0.role', OrganizationRole::Farmer->value)
        ->assertJsonPath('meta.referral_applied', true);

    $user = User::query()->sole();
    $redemption = ReferralRedemption::query()->sole();

    expect($user->organizations)->toHaveCount(1);
    expect($user->organizations->first()->membership->role)->toBe(OrganizationRole::Farmer);
    expect($redemption->organization_id)->toBe($organization->getKey());
    expect($redemption->user_id)->toBe($user->getKey());
});

test('an invalid referral code fails registration', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
        'referral_code' => 'UNKNOWN',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'validation_failed')
        ->assertJsonValidationErrors('referral_code');

    expect(User::query()->count())->toBe(0);
});
