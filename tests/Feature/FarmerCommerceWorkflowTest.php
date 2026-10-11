<?php

declare(strict_types=1);

use App\Enums\AgriculturalOfferCategory;
use App\Models\AgriculturalOffer;
use App\Models\AgriculturalPartner;
use App\Models\AgriculturalServiceRequest;
use App\Models\Farm;
use App\Models\User;

test('farmers only see current offers from verified active partners', function () {
    $farmer = User::factory()->create();
    $verified = AgriculturalPartner::factory()->create();
    $unverified = AgriculturalPartner::factory()->create(['verified_at' => null, 'verified_by_user_id' => null]);
    $market = AgriculturalOffer::factory()->for($verified, 'partner')->create(['category' => AgriculturalOfferCategory::ProducePurchase, 'state' => 'Kano']);
    AgriculturalOffer::factory()->for($unverified, 'partner')->create(['title' => 'Unverified buyer']);
    AgriculturalOffer::factory()->for($verified, 'partner')->create(['title' => 'Expired offer', 'ends_at' => now()->subDay()]);

    $this->actingAs($farmer)
        ->getJson('/api/v1/agricultural-offers?category=produce_purchase&state=Kano')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $market->getKey())
        ->assertJsonPath('data.0.partner.verified', true)
        ->assertJsonMissing(['title' => 'Unverified buyer'])
        ->assertJsonMissing(['title' => 'Expired offer']);
});

test('a farmer can request produce market input finance or machinery services for an owned farm', function (AgriculturalOfferCategory $category) {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create(['state' => 'Kano']);
    $offer = AgriculturalOffer::factory()->create(['category' => $category, 'minimum_quantity' => 2]);

    $this->actingAs($farmer)
        ->postJson('/api/v1/agricultural-service-requests', [
            'offer_id' => $offer->getKey(),
            'farm_id' => $farm->uuid,
            'quantity' => 4,
            'unit' => $offer->unit,
            'budget_amount' => 250000,
            'requested_for' => today()->addWeek()->toDateString(),
            'purpose' => 'I want this service for my active farm season.',
            'consent' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.request_type', $category->value)
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.farm.id', $farm->uuid);

    expect(AgriculturalServiceRequest::query()->whereBelongsTo($farmer)->where('farm_id', $farm->getKey())->exists())->toBeTrue();
})->with([
    'market access' => AgriculturalOfferCategory::ProducePurchase,
    'input finance' => AgriculturalOfferCategory::InputFinance,
    'group input purchase' => AgriculturalOfferCategory::InputPurchase,
    'machinery hire' => AgriculturalOfferCategory::MachineryHire,
]);

test('a farmer cannot request a service with another farmers farm', function () {
    $farmer = User::factory()->create();
    $otherFarm = Farm::factory()->create();
    $offer = AgriculturalOffer::factory()->create();

    $this->actingAs($farmer)
        ->postJson('/api/v1/agricultural-service-requests', [
            'offer_id' => $offer->getKey(),
            'farm_id' => $otherFarm->uuid,
            'quantity' => 5,
            'consent' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('farm_id');
});

test('finite offers cannot be overbooked by farmer requests', function () {
    $farmer = User::factory()->create();
    $farm = Farm::factory()->for($farmer, 'owner')->create();
    $offer = AgriculturalOffer::factory()->create([
        'minimum_quantity' => 2,
        'available_quantity' => 10,
    ]);
    AgriculturalServiceRequest::factory()->for($offer, 'offer')->create([
        'quantity' => 8,
        'status' => 'approved',
    ]);

    $this->actingAs($farmer)->postJson('/api/v1/agricultural-service-requests', [
        'offer_id' => $offer->id,
        'farm_id' => $farm->uuid,
        'quantity' => 3,
        'consent' => true,
    ])->assertUnprocessable()->assertInvalid('quantity');

    $this->actingAs($farmer)->getJson('/api/v1/agricultural-offers')
        ->assertSuccessful()
        ->assertJsonPath('data.0.pricing.remaining_quantity', 2);
});
