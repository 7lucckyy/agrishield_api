<?php

namespace Database\Factories;

use App\Models\AgriculturalOffer;
use App\Models\AgriculturalServiceRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgriculturalServiceRequest>
 */
class AgriculturalServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'agricultural_offer_id' => AgriculturalOffer::factory(),
            'user_id' => User::factory(),
            'farm_id' => Farm::factory(),
            'request_type' => 'produce_purchase',
            'status' => 'submitted',
            'quantity' => 10,
            'unit' => '100kg bag',
            'purpose' => 'I want to supply produce through this verified buying window.',
            'consent_version' => 'agricultural-services-v1',
            'consented_at' => now(),
            'submitted_at' => now(),
        ];
    }
}
