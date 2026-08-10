<?php

declare(strict_types=1);

use App\Enums\CropCycleStatus;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;

test('a farm owner attaches an active crop with valid dates', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $crop = Crop::factory()->create(['active' => true, 'code' => 'MAIZE']);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->subMonth()->toDateString(),
        'expected_harvest_date' => now()->addMonths(3)->toDateString(),
        'season' => '2026-wet',
    ])->assertCreated()
        ->assertJsonPath('data.farm_id', $farm->uuid)
        ->assertJsonPath('data.crop.code', 'MAIZE')
        ->assertJsonPath('data.status', CropCycleStatus::Active->value);

    expect(CropCycle::query()->sole()->status)->toBe(CropCycleStatus::Active);
});

test('a second active crop cycle is rejected by the database constraint', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $crop = Crop::factory()->create(['active' => true]);
    CropCycle::factory()->for($farm)->for($crop)->active()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->toDateString(),
        'status' => CropCycleStatus::Active->value,
    ])->assertConflict()
        ->assertJsonPath('error_code', 'active_cycle_exists');

    expect($farm->cropCycles()->count())->toBe(1);
});

test('inactive crops and invalid harvest dates are rejected', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $crop = Crop::factory()->create(['active' => false]);

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->toDateString(),
        'expected_harvest_date' => now()->subDay()->toDateString(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['crop_id', 'expected_harvest_date']);
});

test('legal crop cycle transitions are applied and terminal cycles cannot reopen', function () {
    $owner = User::factory()->create();
    $cycle = CropCycle::factory()->for(Farm::factory()->for($owner, 'owner'))->active()->create();

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$cycle->farm->uuid}/crop-cycles/{$cycle->getKey()}", [
        'status' => CropCycleStatus::Harvested->value,
        'actual_harvest_date' => now()->toDateString(),
    ])->assertSuccessful()->assertJsonPath('data.status', CropCycleStatus::Harvested->value);

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$cycle->farm->uuid}/crop-cycles/{$cycle->getKey()}", [
        'status' => CropCycleStatus::Active->value,
    ])->assertConflict()->assertJsonPath('error_code', 'invalid_transition');
});

test('harvesting requires an actual harvest date', function () {
    $owner = User::factory()->create();
    $cycle = CropCycle::factory()->for(Farm::factory()->for($owner, 'owner'))->active()->create();

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$cycle->farm->uuid}/crop-cycles/{$cycle->getKey()}", [
        'status' => CropCycleStatus::Harvested->value,
    ])->assertConflict()->assertJsonPath('error_code', 'invalid_transition');
});

test('harvest dates can be updated without resending the planting date', function () {
    $owner = User::factory()->create();
    $cycle = CropCycle::factory()->for(Farm::factory()->for($owner, 'owner'))->create([
        'planting_date' => now()->subMonth(),
    ]);

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$cycle->farm->uuid}/crop-cycles/{$cycle->getKey()}", [
        'expected_harvest_date' => now()->addMonths(4)->toDateString(),
    ])->assertSuccessful()
        ->assertJsonPath('data.expected_harvest_date', now()->addMonths(4)->toDateString());
});

test('expected harvest dates cannot exceed 730 days after planting', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $crop = Crop::factory()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/crop-cycles", [
        'crop_id' => $crop->getKey(),
        'planting_date' => now()->toDateString(),
        'expected_harvest_date' => now()->addDays(731)->toDateString(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('expected_harvest_date');
});

test('planned crop cycles can be deleted but active cycles are retained', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    $planned = CropCycle::factory()->for($farm)->create();

    $this->actingAs($owner)
        ->deleteJson("/api/v1/farms/{$farm->uuid}/crop-cycles/{$planned->getKey()}")
        ->assertNoContent();

    $active = CropCycle::factory()->for($farm)->active()->create();
    $this->actingAs($owner)
        ->deleteJson("/api/v1/farms/{$farm->uuid}/crop-cycles/{$active->getKey()}")
        ->assertConflict()
        ->assertJsonPath('error_code', 'invalid_transition');
});

test('crop cycle history is listed newest first and filterable', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->create();
    CropCycle::factory()->for($farm)->create(['planting_date' => now()->subYear(), 'status' => CropCycleStatus::Abandoned]);
    $active = CropCycle::factory()->for($farm)->active()->create(['planting_date' => now()->subMonth()]);

    $this->actingAs($owner)
        ->getJson("/api/v1/farms/{$farm->uuid}/crop-cycles?filter[status]=active")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->getKey());
});
