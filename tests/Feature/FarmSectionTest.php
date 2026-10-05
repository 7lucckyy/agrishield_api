<?php

declare(strict_types=1);

use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Models\User;

test('a farmer divides one farm into sections with different crops', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 10]);
    $onion = Crop::factory()->create(['name' => 'Onion', 'code' => 'ONION']);
    $tomato = Crop::factory()->create(['name' => 'Tomato', 'code' => 'TOMATO']);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => ' Section A ',
        'crop_id' => $onion->getKey(),
        'area_hectares' => 4,
        'notes' => 'Near the borehole',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Section A')
        ->assertJsonPath('data.crop.name', 'Onion')
        ->assertJsonPath('data.area.hectares', 4)
        ->assertJsonPath('data.area.farm_percentage', 40);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Section B',
        'crop_id' => $tomato->getKey(),
        'area_hectares' => 3.5,
    ])->assertCreated()->assertJsonPath('data.crop.name', 'Tomato');

    $this->actingAs($owner)->getJson("/api/v1/farms/{$farm->uuid}/sections")
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.allocated_hectares', 7.5)
        ->assertJsonPath('meta.remaining_hectares', 2.5);

    expect($farm->sections()->pluck('name')->all())->toBe(['Section A', 'Section B']);
});

test('the combined section area cannot exceed the farm area', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 5]);
    $crop = Crop::factory()->create();
    FarmSection::factory()->for($farm)->for($crop)->create(['area_hectares' => 4, 'area_acres' => 9.8842]);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Section B',
        'crop_id' => $crop->getKey(),
        'area_hectares' => 1.5,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('area_hectares');

    expect($farm->sections()->count())->toBe(1);
});

test('a farmer can change a section crop and area', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 8]);
    $onion = Crop::factory()->create(['name' => 'Onion']);
    $tomato = Crop::factory()->create(['name' => 'Tomato']);
    $section = FarmSection::factory()->for($farm)->for($onion)->create([
        'name' => 'Section A',
        'area_hectares' => 2,
        'area_acres' => 4.9421,
    ]);

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$farm->uuid}/sections/{$section->getKey()}", [
        'crop_id' => $tomato->getKey(),
        'area_hectares' => 3,
    ])->assertSuccessful()
        ->assertJsonPath('data.crop.name', 'Tomato')
        ->assertJsonPath('data.area.hectares', 3);

    expect($section->refresh()->crop_id)->toBe($tomato->getKey())
        ->and((float) $section->area_acres)->toBeGreaterThan(7.41);
});

test('section names are unique within a farm and crops must be active', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $activeCrop = Crop::factory()->create();
    $inactiveCrop = Crop::factory()->create(['active' => false]);
    FarmSection::factory()->for($farm)->for($activeCrop)->create(['name' => 'Section A']);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Section A',
        'crop_id' => $inactiveCrop->getKey(),
        'area_hectares' => 1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'crop_id']);
});

test('farm section access follows the farm permissions', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $crop = Crop::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();

    $this->actingAs($stranger)
        ->getJson("/api/v1/farms/{$farm->uuid}/sections")
        ->assertNotFound();

    $this->actingAs($stranger)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'No access',
        'crop_id' => $crop->getKey(),
        'area_hectares' => 1,
    ])->assertNotFound();
});

test('farm sections are scoped to their parent farm', function () {
    $owner = User::factory()->create();
    $crop = Crop::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $otherFarm = Farm::factory()->for($owner, 'owner')->create();
    $section = FarmSection::factory()->for($farm)->for($crop)->create();

    $this->actingAs($owner)
        ->deleteJson("/api/v1/farms/{$otherFarm->uuid}/sections/{$section->getKey()}")
        ->assertNotFound();
});

test('a farm owner can delete a section', function () {
    $owner = User::factory()->create();
    $section = FarmSection::factory()
        ->for(Farm::factory()->for($owner, 'owner'))
        ->create();

    $this->actingAs($owner)
        ->deleteJson("/api/v1/farms/{$section->farm->uuid}/sections/{$section->getKey()}")
        ->assertNoContent();
});

test('a farmer can create a field boundary with server-derived spatial values', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 200]);
    $crop = Crop::factory()->create();

    $response = $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'North field',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.516, 12.000, 8.518, 12.002),
    ])->assertCreated()
        ->assertJsonPath('data.name', 'North field')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.boundary_geojson.type', 'Polygon');

    expect($response->json('data.area.hectares'))->toBeGreaterThan(4)
        ->and($response->json('data.centroid.latitude'))->not->toBeNull()
        ->and($response->json('data.centroid.longitude'))->not->toBeNull();

    $section = FarmSection::query()->sole();
    expect($section->boundary_hash)->not->toBeNull()
        ->and($section->boundary_geojson)->toBeArray();
});

test('field boundaries must remain inside their farm on create and update', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 200]);
    $crop = Crop::factory()->create();
    $inside = fieldBoundary(8.516, 12.000, 8.517, 12.001);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Outside',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.518, 12.000, 8.520, 12.001),
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');

    $response = $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Inside',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => $inside,
    ])->assertCreated();

    $section = FarmSection::query()->findOrFail($response->json('data.id'));
    $originalHash = $section->boundary_hash;
    $this->actingAs($owner)->patchJson("/api/v1/farms/{$farm->uuid}/sections/{$section->getKey()}", [
        'boundary_geojson' => fieldBoundary(8.518, 12.000, 8.520, 12.001),
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');

    expect($section->refresh()->boundary_hash)->toBe($originalHash);
});

test('a farm boundary cannot be moved away from an existing field', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 200]);
    $crop = Crop::factory()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'North field',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.516, 12.000, 8.517, 12.001),
    ])->assertCreated();

    $originalHash = $farm->boundary_hash;
    $this->actingAs($owner)->patchJson("/api/v1/farms/{$farm->uuid}", [
        'boundary_geojson' => fieldBoundary(8.520, 12.000, 8.522, 12.002),
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');

    expect($farm->refresh()->boundary_hash)->toBe($originalHash);
});

test('a farm boundary cannot shrink below its allocated area-only sections', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 200]);
    FarmSection::factory()->for($farm)->create(['area_hectares' => 4]);

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$farm->uuid}", [
        'boundary_geojson' => fieldBoundary(8.515, 11.999, 8.5152, 11.9992),
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');
});

test('a field may touch the farm boundary but may not cross its concave gap', function () {
    $owner = User::factory()->create();
    $boundary = [
        'type' => 'Polygon',
        'coordinates' => [[[8.515, 11.999], [8.519, 11.999], [8.519, 12.002], [8.518, 12.002], [8.518, 12.000], [8.516, 12.000], [8.516, 12.002], [8.515, 12.002], [8.515, 11.999]]],
    ];
    $farm = Farm::factory()->for($owner, 'owner')->create(['boundary_geojson' => $boundary, 'area_hectares' => 200]);
    $crop = Crop::factory()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Touching field',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.515, 11.999, 8.516, 12.000),
    ])->assertCreated();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Across gap',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.5155, 12.0005, 8.5185, 12.001),
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');
});

test('a field may occupy one multipolygon farm parcel but may not span the gap', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create([
        'boundary_geojson' => [
            'type' => 'MultiPolygon',
            'coordinates' => [
                fieldBoundary(8.510, 12.000, 8.512, 12.002)['coordinates'],
                fieldBoundary(8.516, 12.000, 8.518, 12.002)['coordinates'],
            ],
        ],
        'area_hectares' => 200,
    ]);
    $crop = Crop::factory()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Second parcel',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.5162, 12.0002, 8.5178, 12.0018),
    ])->assertCreated();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Across parcels',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => fieldBoundary(8.511, 12.0005, 8.517, 12.0015),
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');
});

test('a field respects a farm exclusion hole while matching its boundary', function () {
    $owner = User::factory()->create();
    $outer = fieldBoundary(8.515, 11.999, 8.519, 12.003);
    $hole = fieldBoundary(8.516, 12.000, 8.517, 12.001)['coordinates'][0];
    $boundary = [
        'type' => 'Polygon',
        'coordinates' => [$outer['coordinates'][0], $hole],
    ];
    $farm = Farm::factory()->for($owner, 'owner')->create([
        'boundary_geojson' => $boundary,
        'area_hectares' => 200,
    ]);
    $crop = Crop::factory()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Whole permitted parcel',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => $boundary,
    ])->assertCreated();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Covers excluded area',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => $outer,
    ])->assertUnprocessable()->assertJsonValidationErrors('boundary_geojson');
});

function fieldBoundary(float $minLongitude, float $minLatitude, float $maxLongitude, float $maxLatitude): array
{
    return [
        'type' => 'Polygon',
        'coordinates' => [[
            [$minLongitude, $minLatitude],
            [$maxLongitude, $minLatitude],
            [$maxLongitude, $maxLatitude],
            [$minLongitude, $maxLatitude],
            [$minLongitude, $minLatitude],
        ]],
    ];
}

test('invalid field geometry is rejected without replacing the existing area workflow', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $crop = Crop::factory()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sections", [
        'name' => 'Invalid field',
        'crop_id' => $crop->getKey(),
        'boundary_geojson' => [
            'type' => 'Polygon',
            'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 1]]],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('boundary_geojson');

    expect(FarmSection::query()->count())->toBe(0);
});

test('farm details include section assignments and available area', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create(['area_hectares' => 6]);
    $crop = Crop::factory()->create(['name' => 'Pepper']);
    FarmSection::factory()->for($farm)->for($crop)->create([
        'name' => 'Section A',
        'area_hectares' => 2,
        'area_acres' => 4.9421,
    ]);

    $this->actingAs($owner)->getJson("/api/v1/farms/{$farm->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('data.sections.0.crop.name', 'Pepper')
        ->assertJsonPath('data.section_summary.count', 1)
        ->assertJsonPath('data.section_summary.allocated_hectares', 2)
        ->assertJsonPath('data.section_summary.remaining_hectares', 4);
});
