<?php

declare(strict_types=1);

use App\Enums\AssetFinanceStatus;
use App\Enums\EquipmentAcquisitionMode;
use App\Enums\IrrigationContext;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Database\Seeders\EquipmentCatalogueSeeder;

beforeEach(function () {
    config()->set('equipment.requests_enabled', true);
});

test('an independent farmer can view asset products and apply with an owned farm', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create(['organization_id' => null]);
    CropCycle::factory()->for($farm)->active()->create(['irrigation_context' => IrrigationContext::Irrigated]);
    $this->seed(EquipmentCatalogueSeeder::class);
    $product = AssetFinanceProduct::query()->where('name', 'High-capacity solar pump')->firstOrFail();

    $this->actingAs($farmer)
        ->getJson('/api/v1/asset-finance/products?farm_id='.$farm->uuid)
        ->assertSuccessful()
        ->assertJsonMissingPath('data.0.partner');

    $this->actingAs($farmer)
        ->postJson('/api/v1/asset-finance/applications', [
            'farm_id' => $farm->uuid,
            'asset_finance_product_id' => $product->getKey(),
            'acquisition_mode' => EquipmentAcquisitionMode::Lease->value,
            'quantity' => 1,
            'requested_amount' => 650000,
            'purpose' => 'I need irrigation equipment for my maize farm during the dry season.',
            'consent' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', AssetFinanceStatus::Submitted->value)
        ->assertJsonPath('data.organization_id', null)
        ->assertJsonPath('data.acquisition_mode', 'lease')
        ->assertJsonPath('data.farm.id', $farm->uuid)
        ->assertJsonMissingPath('data.product.partner');

    $application = AssetFinanceApplication::query()->where('applicant_user_id', $farmer->getKey())->latest('id')->firstOrFail();
    expect($application->organization_id)->toBeNull()
        ->and($application->applicant_user_id)->toBe($farmer->getKey())
        ->and($application->consent_channel)->toBe('farmer_mobile');

    $this->actingAs($farmer)
        ->getJson('/api/v1/asset-finance/applications')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

test('a farmer cannot apply with another farmers farm', function () {
    $farmer = User::factory()->create();
    $otherFarm = Farm::factory()->create();
    $product = AssetFinanceProduct::factory()->create();

    $this->actingAs($farmer)
        ->postJson('/api/v1/asset-finance/applications', [
            'farm_id' => $otherFarm->uuid,
            'asset_finance_product_id' => $product->getKey(),
            'quantity' => 1,
            'requested_amount' => 650000,
            'purpose' => 'I need irrigation equipment for my maize farm during the dry season.',
            'consent' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('farm_id');
});
