<?php

namespace Database\Factories;

use App\Models\FarmerAlertPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FarmerAlertPreference>
 */
class FarmerAlertPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'in_app_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => true,
            'whatsapp_enabled' => false,
            'voice_call_enabled' => false,
            'phone' => '+2348012345678',
            'language' => 'en',
            'minimum_severity' => 'medium',
            'consent' => true,
            'consented_at' => now(),
        ];
    }
}
