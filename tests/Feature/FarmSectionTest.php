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
