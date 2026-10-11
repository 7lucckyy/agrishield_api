<?php

use App\Enums\GlobalRole;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Models\SatelliteObservation;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('productivity zones remain unavailable until comparable real section data exists', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->create(['owner_user_id' => $farmer->id]);
    $crop = Crop::factory()->create();
    $sections = collect(range(1, 3))->map(fn ($index) => FarmSection::factory()->create([
        'farm_id' => $farm->id, 'crop_id' => $crop->id, 'name' => 'Plot '.$index, 'boundary_geojson' => validFarmBoundary($index),
    ]));

    $this->actingAs($farmer)->getJson(route('farms.productivity-zones.show', $farm))
        ->assertOk()->assertJsonPath('data.crop_groups.'.$crop->id.'.state', 'insufficient_data');

    foreach ($sections as $index => $section) {
        SatelliteObservation::factory()->create([
            'farm_id' => $farm->id, 'farm_section_id' => $section->id,
            'value' => [0.3, 0.5, 0.7][$index], 'source' => 'sentinel2',
            'captured_at' => now()->subDays(2),
        ]);
    }

    $this->actingAs($farmer)->getJson(route('farms.productivity-zones.show', $farm))
        ->assertOk()->assertJsonPath('data.crop_groups.'.$crop->id.'.state', 'ready')
        ->assertJsonPath('data.crop_groups.'.$crop->id.'.zones.0.zone', 'lower_relative_vigor');
    $this->actingAs($farmer)->getJson(route('farms.satellite-observations.index', $farm))
        ->assertOk()->assertJsonCount(0, 'data');
    $this->actingAs($farmer)->getJson(route('farms.satellite-observations.summary', $farm))
        ->assertOk()->assertJsonPath('meta.point_count', 0);
});

test('only a platform admin can import a recent traceable section observation', function () {
    Role::findOrCreate(GlobalRole::PlatformAdmin->value, 'web');
    $admin = User::factory()->create()->assignRole(GlobalRole::PlatformAdmin->value);
    $farmer = User::factory()->create();
    $farm = Farm::factory()->create(['owner_user_id' => $farmer->id]);
    $section = FarmSection::factory()->create(['farm_id' => $farm->id]);
    $payload = [
        'value' => 0.61, 'captured_at' => now()->subDay()->toISOString(),
        'quality_flag' => 'good', 'source' => 'sentinel2',
        'external_reference' => 'scene-123-section-1', 'resolution_meters' => 10,
    ];
    $route = route('farms.sections.ndvi-observations.store', [$farm, $section]);

    $this->actingAs($admin)->postJson($route, [...$payload, 'source' => 'fake'])->assertUnprocessable();
    $this->actingAs($admin)->postJson($route, $payload)->assertCreated()->assertJsonPath('data.section_id', $section->id);
    $this->actingAs($admin)->postJson($route, $payload)->assertUnprocessable();
});

test('farmer cannot import satellite measurements', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->create(['owner_user_id' => $farmer->id]);
    $section = FarmSection::factory()->create(['farm_id' => $farm->id]);

    $this->actingAs($farmer)->postJson(route('farms.sections.ndvi-observations.store', [$farm, $section]), [
        'value' => 0.61, 'captured_at' => now()->toISOString(), 'quality_flag' => 'good',
        'source' => 'sentinel2', 'external_reference' => 'scene-123-section-1', 'resolution_meters' => 10,
    ])->assertForbidden();
});

test('zone calculation excludes fake observations and refuses mixed satellite sources', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->create(['owner_user_id' => $farmer->id]);
    $crop = Crop::factory()->create();
    $sections = collect(range(1, 3))->map(fn ($index) => FarmSection::factory()->create([
        'farm_id' => $farm->id, 'crop_id' => $crop->id, 'boundary_geojson' => validFarmBoundary($index),
    ]));

    foreach ($sections as $index => $section) {
        SatelliteObservation::factory()->create([
            'farm_id' => $farm->id,
            'farm_section_id' => $section->id,
            'value' => [0.2, 0.4, 0.7][$index],
            'source' => $index === 2 ? 'fake' : 'sentinel2',
            'captured_at' => now()->subDay(),
        ]);
    }

    $route = route('farms.productivity-zones.show', $farm);
    $this->actingAs($farmer)->getJson($route)
        ->assertJsonPath('data.crop_groups.'.$crop->id.'.state', 'insufficient_data');

    SatelliteObservation::factory()->create([
        'farm_id' => $farm->id, 'farm_section_id' => $sections->last()->id,
        'value' => 0.7, 'source' => 'landsat8', 'captured_at' => now()->subDay(),
    ]);

    $this->actingAs($farmer)->getJson($route)
        ->assertJsonPath('data.crop_groups.'.$crop->id.'.state', 'non_comparable_sources');
});
