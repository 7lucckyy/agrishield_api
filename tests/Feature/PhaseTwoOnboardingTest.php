<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Crop;
use App\Models\Organization;
use App\Models\User;

test('phase two onboarding works from referral registration through farm crop attachment', function () {
    $organization = Organization::factory()->create(['referral_code' => 'ONBOARD26']);
    $crop = Crop::factory()->create(['active' => true, 'code' => 'MAIZE']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'email' => 'amina@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
        'referral_code' => 'ONBOARD26',
    ])->assertCreated()
        ->assertJsonPath('data.organizations.0.role', OrganizationRole::Farmer->value);

    $farmer = User::query()->sole();
    $farmResponse = $this->actingAs($farmer)->postJson('/api/v1/farms', [
        'name' => 'North Plot',
        'organization_id' => $organization->getKey(),
        'boundary_geojson' => validFarmBoundary(),
        'area_hectares' => 999999,
        'locality' => 'Dawakin Kudu',
        'state' => 'Kano',
        'country' => 'NG',
    ])->assertCreated()
        ->assertJsonPath('data.organization.id', $organization->getKey());

    $farmId = $farmResponse->json('data.id');

    $this->actingAs($farmer)->postJson("/api/v1/farms/{$farmId}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->subMonth()->toDateString(),
        'expected_harvest_date' => now()->addMonths(3)->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.farm_id', $farmId)
        ->assertJsonPath('data.crop.code', 'MAIZE')
        ->assertJsonPath('data.status', 'active');

    expect((float) $farmer->farms()->sole()->area_hectares)->not->toBe(999999.0);
});
