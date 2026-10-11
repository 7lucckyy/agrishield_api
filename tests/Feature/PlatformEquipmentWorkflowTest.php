<?php

use App\Enums\AssetFinanceStatus;
use App\Enums\FinancePartnerType;
use App\Enums\GlobalRole;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\FinancePartner;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('platform admin can grant and revoke equipment desk role while other users cannot', function () {
    Role::findOrCreate(GlobalRole::PlatformAdmin->value, 'web');
    Role::findOrCreate(GlobalRole::EquipmentManager->value, 'web');
    $admin = User::factory()->create()->assignRole(GlobalRole::PlatformAdmin->value);
    $manager = User::factory()->create();
    $farmer = User::factory()->create();

    $farmerResponse = $this->actingAs($farmer)->patch(route('platform.users.equipment-manager.update', $manager), ['enabled' => 1]);
    expect($farmerResponse->status())->toBe(403);
    $this->actingAs($admin)->patch(route('platform.users.equipment-manager.update', $manager), ['enabled' => 1])->assertRedirect();
    expect($manager->fresh()->hasRole(GlobalRole::EquipmentManager->value))->toBeTrue();
    expect($manager->can('accessPlatform'))->toBeFalse();
    $this->actingAs($manager)->get(route('platform.equipment.index'))->assertOk()->assertSee('Equipment desk');
    $this->actingAs($manager)->get(route('platform.users'))->assertForbidden();
    $this->actingAs($admin)->patch(route('platform.users.equipment-manager.update', $manager), ['enabled' => 0])->assertRedirect();
    expect($manager->fresh()->hasRole(GlobalRole::EquipmentManager->value))->toBeFalse();
    $manager->unsetRelation('roles');
    $this->actingAs($manager)->get(route('platform.equipment.index'))->assertForbidden();
});

test('equipment manager reviews only farmer equipment requests and records transition events', function () {
    Role::findOrCreate(GlobalRole::EquipmentManager->value, 'web');
    $manager = User::factory()->create()->assignRole(GlobalRole::EquipmentManager->value);
    $desk = FinancePartner::factory()->create(['type' => FinancePartnerType::EquipmentDesk]);
    $product = AssetFinanceProduct::factory()->create(['finance_partner_id' => $desk->id]);
    $application = AssetFinanceApplication::factory()->create(['organization_id' => null, 'asset_finance_product_id' => $product->id]);
    $other = AssetFinanceApplication::factory()->create();

    $this->actingAs($manager)->get(route('platform.equipment.index'))
        ->assertOk()->assertSee($application->farm->name)->assertDontSee($other->farm->name);
    $this->actingAs($manager)->patch(route('platform.equipment.update', $other), [
        'status' => AssetFinanceStatus::UnderReview->value,
        'decision_note' => 'The application has been reviewed by the desk.',
    ])->assertForbidden();
    $this->actingAs($manager)->patch(route('platform.equipment.update', $application), [
        'status' => AssetFinanceStatus::UnderReview->value,
        'decision_note' => 'The request is being reviewed by the equipment desk.',
    ])->assertRedirect();
    expect($application->fresh()->status)->toBe(AssetFinanceStatus::UnderReview)
        ->and($application->events()->count())->toBe(1);
});

test('equipment delivery requires explicit verification', function () {
    Role::findOrCreate(GlobalRole::EquipmentManager->value, 'web');
    $manager = User::factory()->create()->assignRole(GlobalRole::EquipmentManager->value);
    $desk = FinancePartner::factory()->create(['type' => FinancePartnerType::EquipmentDesk]);
    $product = AssetFinanceProduct::factory()->create(['finance_partner_id' => $desk->id]);
    $application = AssetFinanceApplication::factory()->create(['organization_id' => null, 'asset_finance_product_id' => $product->id, 'status' => AssetFinanceStatus::AssetOrdered]);
    $payload = ['status' => AssetFinanceStatus::Delivered->value, 'decision_note' => 'I confirmed handover with the registered farmer.'];

    $this->actingAs($manager)->patch(route('platform.equipment.update', $application), $payload)->assertSessionHasErrors('delivery_confirmed');
    expect($application->fresh()->status)->toBe(AssetFinanceStatus::AssetOrdered);
    $this->actingAs($manager)->patch(route('platform.equipment.update', $application), [...$payload, 'delivery_confirmed' => '1'])->assertRedirect();
    expect($application->fresh()->delivery_verified_by_user_id)->toBe($manager->id);
});

test('equipment manager can pause a desk product and set request modes without editing other products', function () {
    Role::findOrCreate(GlobalRole::EquipmentManager->value, 'web');
    $manager = User::factory()->create()->assignRole(GlobalRole::EquipmentManager->value);
    $desk = FinancePartner::factory()->create(['type' => FinancePartnerType::EquipmentDesk]);
    $product = AssetFinanceProduct::factory()->create([
        'finance_partner_id' => $desk->id,
        'metadata' => ['equipment_key' => 'tractor', 'farm_size_tiers' => ['large'], 'acquisition_options' => ['lease']],
    ]);
    $other = AssetFinanceProduct::factory()->create();
    $payload = ['is_active' => '0', 'acquisition_options' => ['lease', 'finance_purchase']];

    $this->actingAs($manager)->patch(route('platform.equipment.products.update', $other), $payload)->assertForbidden();
    $this->actingAs($manager)->patch(route('platform.equipment.products.update', $product), $payload)->assertRedirect();
    expect($product->fresh()->is_active)->toBeFalse()
        ->and($product->fresh()->metadata['farm_size_tiers'])->toBe(['large'])
        ->and($product->fresh()->metadata['acquisition_options'])->toBe(['lease', 'finance_purchase']);
});
