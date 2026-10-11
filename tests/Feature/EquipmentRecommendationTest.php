<?php

declare(strict_types=1);

use App\Enums\IrrigationContext;
use App\Models\AssetFinanceProduct;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Database\Seeders\EquipmentCatalogueSeeder;

beforeEach(function () {
    config()->set('equipment.requests_enabled', true);
});

test('irrigated large farms see only large irrigation equipment with lease options', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create(['area_hectares' => 20]);
    CropCycle::factory()->for($farm)->active()->create(['irrigation_context' => IrrigationContext::Irrigated]);
    $this->seed(EquipmentCatalogueSeeder::class);

    $response = $this->actingAs($farmer)->getJson('/api/v1/asset-finance/products?farm_id='.$farm->uuid)
        ->assertOk()->assertJsonCount(3, 'data');
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('High-capacity solar pump', 'Large-field drip system', 'Large water storage system')
        ->not->toContain('Tractor', 'Solar water pump');
    $response->assertJsonMissingPath('data.0.partner');
});

test('rainfed large farms see tractors but not irrigation equipment', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create(['area_hectares' => 20]);
    CropCycle::factory()->for($farm)->active()->create(['irrigation_context' => IrrigationContext::RainFed]);
    $this->seed(EquipmentCatalogueSeeder::class);

    $response = $this->actingAs($farmer)->getJson('/api/v1/asset-finance/products?farm_id='.$farm->uuid)->assertOk();
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Tractor')->not->toContain('High-capacity solar pump');
    $tractor = collect($response->json('data'))->firstWhere('name', 'Tractor');
    expect($tractor['acquisition_options'])->toContain('lease');
});

test('equipment requests reject products outside farm size and irrigation context', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create(['area_hectares' => 1]);
    CropCycle::factory()->for($farm)->active()->create(['irrigation_context' => IrrigationContext::Irrigated]);
    $this->seed(EquipmentCatalogueSeeder::class);
    $response = $this->actingAs($farmer)->getJson('/api/v1/asset-finance/products?farm_id='.$farm->uuid)->assertOk();
    expect(collect($response->json('data'))->pluck('name'))->toContain('Solar water pump')->not->toContain('Tractor');

    $this->actingAs($farmer)->postJson('/api/v1/asset-finance/applications', [
        'farm_id' => $farm->uuid,
        'asset_finance_product_id' => AssetFinanceProduct::query()->where('name', 'Tractor')->firstOrFail()->getKey(),
        'acquisition_mode' => 'lease',
        'quantity' => 1,
        'requested_amount' => 300000,
        'purpose' => 'I want a tractor to prepare my small irrigated field.',
        'consent' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('asset_finance_product_id');
});

test('equipment is withheld until a farm has an active crop cycle', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();
    $this->seed(EquipmentCatalogueSeeder::class);

    $this->actingAs($farmer)->getJson('/api/v1/asset-finance/products?farm_id='.$farm->uuid)
        ->assertOk()->assertJsonCount(0, 'data');
});

test('equipment requests remain closed until operations enable them', function () {
    config()->set('equipment.requests_enabled', false);
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();

    $this->actingAs($farmer)->getJson('/api/v1/asset-finance/products?farm_id='.$farm->uuid)
        ->assertServiceUnavailable();
});
