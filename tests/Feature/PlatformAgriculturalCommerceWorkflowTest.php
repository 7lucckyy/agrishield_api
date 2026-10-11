<?php

declare(strict_types=1);

use App\Enums\AgriculturalOfferCategory;
use App\Enums\AgriculturalServiceRequestStatus;
use App\Enums\GlobalRole;
use App\Models\AgriculturalOffer;
use App\Models\AgriculturalPartner;
use App\Models\AgriculturalServiceRequest;
use App\Models\Crop;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('platform admin verifies partners and publishes farmer offers', function () {
    Role::findOrCreate(GlobalRole::PlatformAdmin->value, 'web');
    $administrator = User::factory()->create()->assignRole(GlobalRole::PlatformAdmin->value);
    $crop = Crop::factory()->create(['active' => true]);

    $this->actingAs($administrator)->post(route('platform.commerce.partners.store'), [
        'name' => 'Kano Verified Produce Aggregator',
        'type' => 'aggregator',
        'contact_phone' => '+2348012345678',
        'state' => 'Kano',
        'locality' => 'Bichi',
        'verification_note' => 'Corporate registration and aggregation centre were reviewed by operations.',
    ])->assertRedirect(route('platform.commerce.index'));

    $partner = AgriculturalPartner::query()->sole();
    expect($partner->verified_by_user_id)->toBe($administrator->id)
        ->and($partner->verified_at)->not->toBeNull();

    $this->actingAs($administrator)->post(route('platform.commerce.offers.store'), [
        'agricultural_partner_id' => $partner->id,
        'crop_id' => $crop->id,
        'category' => AgriculturalOfferCategory::ProducePurchase->value,
        'title' => 'Maize aggregation buying window',
        'description' => 'Confirmed delivery slots for graded maize from registered farmers.',
        'pricing_model' => 'indicative',
        'unit_price' => 85000,
        'unit' => '100kg bag',
        'minimum_quantity' => 5,
        'available_quantity' => 500,
        'state' => 'Kano',
        'requirements_text' => "Moisture check required\nBring a valid farmer ID",
        'ends_at' => now()->addMonth()->toDateString(),
    ])->assertRedirect(route('platform.commerce.index'));

    $offer = AgriculturalOffer::query()->sole();
    expect($offer->partner->is($partner))->toBeTrue()
        ->and($offer->crop->is($crop))->toBeTrue()
        ->and($offer->requirements)->toBe(['Moisture check required', 'Bring a valid farmer ID']);
});

test('platform request review enforces auditable forward-only transitions', function () {
    Role::findOrCreate(GlobalRole::PlatformAdmin->value, 'web');
    $administrator = User::factory()->create()->assignRole(GlobalRole::PlatformAdmin->value);
    $serviceRequest = AgriculturalServiceRequest::factory()->create();

    $this->actingAs($administrator)->patch(route('platform.commerce.requests.update', $serviceRequest), [
        'status' => AgriculturalServiceRequestStatus::Approved->value,
        'decision_note' => 'The partner approved the request without completing review.',
    ])->assertSessionHasErrors('status');

    $this->actingAs($administrator)->patch(route('platform.commerce.requests.update', $serviceRequest), [
        'status' => AgriculturalServiceRequestStatus::Reviewing->value,
        'decision_note' => 'The partner is confirming stock, timing, and the farmer delivery location.',
    ])->assertRedirect(route('platform.commerce.index'));

    $serviceRequest->refresh();
    expect($serviceRequest->status)->toBe(AgriculturalServiceRequestStatus::Reviewing)
        ->and($serviceRequest->metadata['status_history'][0]['user_id'])->toBe($administrator->id)
        ->and($serviceRequest->metadata['status_history'][0]['status'])->toBe(AgriculturalServiceRequestStatus::Reviewing->value);
});

test('farmers cannot access partner verification operations', function () {
    $farmer = User::factory()->create();

    $this->actingAs($farmer)->get(route('platform.commerce.index'))->assertForbidden();
    $this->actingAs($farmer)->post(route('platform.commerce.partners.store'), [])->assertForbidden();
});
