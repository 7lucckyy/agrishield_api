<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MetricType;
use App\Enums\QualityFlag;
use App\Models\Farm;
use App\Models\SatelliteObservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SatelliteObservation> */
class SatelliteObservationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'metric_type' => MetricType::Ndvi,
            'value' => fake()->randomFloat(4, -1, 1),
            'unit' => 'index',
            'resolution_meters' => 10,
            'captured_at' => now()->subDays(5),
            'fetched_at' => now(),
            'next_expected_update_at' => now()->addDays(5),
            'quality_flag' => QualityFlag::Good,
            'source' => 'fake',
            'external_reference' => fake()->unique()->uuid(),
        ];
    }
}
