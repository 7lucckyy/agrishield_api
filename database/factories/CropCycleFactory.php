<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CropCycleStatus;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<CropCycle> */
class CropCycleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $plantingDate = fake()->dateTimeBetween('-6 months', '-1 month');

        return [
            'farm_id' => Farm::factory(),
            'crop_id' => Crop::factory(),
            'planting_date' => $plantingDate,
            'expected_harvest_date' => fn (array $attributes): Carbon => Carbon::parse(
                $attributes['planting_date'],
            )->addDays(120),
            'season' => fake()->year().'-wet',
            'status' => CropCycleStatus::Planned,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => CropCycleStatus::Active]);
    }
}
