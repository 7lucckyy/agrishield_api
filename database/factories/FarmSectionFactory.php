<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FarmSection>
 */
class FarmSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $areaHectares = fake()->randomFloat(4, 0.1, 3);

        return [
            'farm_id' => Farm::factory(),
            'crop_id' => Crop::factory(),
            'name' => fake()->unique()->bothify('Section ?#'),
            'area_hectares' => $areaHectares,
            'area_acres' => $areaHectares * 2.47105381,
            'position' => fake()->numberBetween(1, 20),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
