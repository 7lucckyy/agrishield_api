<?php

namespace Database\Factories;

use App\Enums\AgriculturalPartnerType;
use App\Models\AgriculturalPartner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgriculturalPartner>
 */
class AgriculturalPartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(AgriculturalPartnerType::cases()),
            'contact_phone' => '+234'.fake()->numerify('80########'),
            'state' => 'Kano',
            'verified_at' => now(),
            'verified_by_user_id' => User::factory(),
            'is_active' => true,
            'metadata' => ['verification_note' => 'Documents reviewed for testing.'],
        ];
    }
}
