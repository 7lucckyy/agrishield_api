<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CropCategory;
use App\Models\Crop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Crop>
 */
class CropFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'scientific_name' => fake()->optional()->words(2, true),
            'code' => Str::upper(Str::random(12)),
            'category' => fake()->randomElement(CropCategory::cases()),
            'default_cycle_days' => fake()->numberBetween(45, 365),
            'active' => true,
        ];
    }
}
