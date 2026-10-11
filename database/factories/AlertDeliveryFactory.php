<?php

namespace Database\Factories;

use App\Models\Advisory;
use App\Models\AlertDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertDelivery>
 */
class AlertDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advisory_id' => Advisory::factory(),
            'user_id' => User::factory(),
            'channel' => 'in_app',
            'status' => 'pending',
            'attempts' => 0,
        ];
    }
}
