<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ReferralRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralRedemption>
 */
class ReferralRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'referral_code' => fake()->bothify('REF#####'),
            'ip_address' => fake()->ipv4(),
            'redeemed_at' => now(),
        ];
    }
}
