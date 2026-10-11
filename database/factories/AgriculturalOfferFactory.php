<?php

namespace Database\Factories;

use App\Enums\AgriculturalOfferCategory;
use App\Models\AgriculturalOffer;
use App\Models\AgriculturalPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgriculturalOffer>
 */
class AgriculturalOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agricultural_partner_id' => AgriculturalPartner::factory(),
            'category' => AgriculturalOfferCategory::ProducePurchase,
            'title' => 'Verified maize buying window',
            'description' => 'Deliver graded maize to the listed aggregation point after confirmation.',
            'pricing_model' => 'indicative',
            'unit_price' => 85000,
            'currency' => 'NGN',
            'unit' => '100kg bag',
            'minimum_quantity' => 5,
            'available_quantity' => 500,
            'state' => 'Kano',
            'requirements' => ['Moisture and quality checks apply'],
            'fulfillment_channels' => ['aggregation_centre'],
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
