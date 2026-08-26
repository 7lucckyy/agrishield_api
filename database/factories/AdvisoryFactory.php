<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdvisorySeverity;
use App\Enums\AdvisoryType;
use App\Models\Advisory;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Advisory> */
class AdvisoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'type' => AdvisoryType::PestWarning,
            'title' => fake()->sentence(),
            'summary' => fake()->paragraph(),
            'severity' => AdvisorySeverity::Medium,
            'observed_at' => now(),
            'valid_from' => now(),
            'valid_until' => now()->addWeek(),
            'source' => 'fake',
            'dedupe_key' => hash('sha256', fake()->unique()->uuid()),
        ];
    }
}
