<?php

declare(strict_types=1);

use App\Enums\CropCycleStatus;
use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Enums\ProviderStatus;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\IntegrationAccount;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\GlobalRoleSeeder;

test('the public health endpoint exposes liveness without internals', function () {
    $this->getJson('/api/v1/health')
        ->assertSuccessful()
        ->assertExactJson(['status' => 'ok']);
});

test('only platform administrators can inspect detailed health', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/health/detailed')->assertForbidden();
});

test('platform administrators receive dependency and sync health', function () {
    $this->seed(GlobalRoleSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole(GlobalRole::PlatformAdmin->value);

    $this->actingAs($administrator)->getJson('/api/v1/health/detailed')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['database', 'cache', 'queue', 'provider', 'last_successful_sync']])
        ->assertJsonPath('data.database.reachable', true);
});

test('organization viewers receive the documented overview aggregates', function () {
    $organization = Organization::factory()->create();
    $agronomist = User::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $crop = Crop::factory()->create(['name' => 'Maize']);
    $activeFarm = Farm::factory()->for($organization)->create(['provider_status' => ProviderStatus::Registered, 'area_hectares' => 10]);
    Farm::factory()->for($organization)->create(['provider_status' => ProviderStatus::Pending, 'area_hectares' => 5]);
    CropCycle::factory()->for($activeFarm)->for($crop)->create(['status' => CropCycleStatus::Active]);

    $this->actingAs($agronomist)->getJson("/api/v1/organizations/{$organization->getKey()}/overview")
        ->assertSuccessful()
        ->assertJsonPath('data.farms.total', 2)
        ->assertJsonPath('data.farms.pending_registration', 1)
        ->assertJsonPath('data.area.hectares', 15)
        ->assertJsonPath('data.crops.0.name', 'Maize')
        ->assertJsonPath('meta.cache_ttl_seconds', 600);
});

test('password reset requests do not disclose whether an account exists', function () {
    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'unknown@example.com'])
        ->assertAccepted()
        ->assertJsonPath('data.message', 'If the account exists, a reset link has been sent.');
});

test('platform administrators manage organizations and integrations without exposing secrets', function () {
    $this->seed(GlobalRoleSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole(GlobalRole::PlatformAdmin->value);
    $integration = IntegrationAccount::factory()->fakeProvider()->create([
        'credentials_ref' => 'FAKE_SECRET',
        'config' => ['token' => 'never-return-this'],
    ]);

    $this->actingAs($administrator);
    $organizationResponse = $this->postJson('/api/v1/organizations', ['name' => 'Northern Growers', 'country' => 'NG'])
        ->assertCreated()->assertJsonPath('data.name', 'Northern Growers');
    $this->getJson('/api/v1/organizations')->assertSuccessful()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/integrations')->assertSuccessful()
        ->assertJsonMissing(['credentials_ref' => 'FAKE_SECRET'])
        ->assertJsonMissing(['token' => 'never-return-this']);
    $this->patchJson("/api/v1/integrations/{$integration->getKey()}", ['label' => 'Primary fake'])
        ->assertSuccessful()->assertJsonPath('data.label', 'Primary fake');

    expect(Organization::query()->whereKey($organizationResponse->json('data.id'))->exists())->toBeTrue()
        ->and(AuditLog::query()->count())->toBe(2);
});

test('organization administrators update their organization', function () {
    $organization = Organization::factory()->create();
    $administrator = User::factory()->create();
    attachOrganizationRole($administrator, $organization, OrganizationRole::OrganizationAdmin);

    $this->actingAs($administrator);
    $this->patchJson("/api/v1/organizations/{$organization->getKey()}", ['name' => 'Updated Growers'])
        ->assertSuccessful()->assertJsonPath('data.name', 'Updated Growers');
});
