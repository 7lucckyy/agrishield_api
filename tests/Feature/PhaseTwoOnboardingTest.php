<?php

declare(strict_types=1);

use App\Models\Crop;
use App\Models\User;

test('phase two onboarding works from registration through farm crop attachment', function () {
    $crop = Crop::factory()->create(['active' => true, 'code' => 'MAIZE']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Amina Bello',
        'phone' => '+2348012345678',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ])->assertCreated();

    $farmer = User::query()->sole();
    $farmResponse = $this->actingAs($farmer)->postJson('/api/v1/farms', [
        'name' => 'North Plot',
        'boundary_geojson' => validFarmBoundary(),
        'area_hectares' => 999999,
        'locality' => 'Dawakin Kudu',
        'state' => 'Kano',
        'country' => 'NG',
    ])->assertCreated();

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
