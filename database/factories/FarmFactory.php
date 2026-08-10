<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FarmStatus;
use App\Enums\ProviderStatus;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Farm> */
class FarmFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $boundary = [
            'type' => 'Polygon',
            'coordinates' => [[
                [8.515, 12.0022],
                [8.519, 12.0022],
                [8.519, 11.999],
                [8.515, 11.999],
                [8.515, 12.0022],
            ]],
        ];

        return [
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => User::factory(),
            'organization_id' => null,
            'name' => fake()->words(2, true).' Plot',
            'boundary_geojson' => $boundary,
            'boundary_hash' => hash('sha256', json_encode($boundary, JSON_THROW_ON_ERROR)),
            'centroid_latitude' => 12.0006,
            'centroid_longitude' => 8.517,
            'area_hectares' => 15.4372,
            'area_acres' => 38.1475,
            'country' => 'NG',
            'status' => FarmStatus::Active,
            'provider_status' => ProviderStatus::Pending,
        ];
    }

    public function registered(): static
    {
        return $this->state(fn (): array => ['provider_status' => ProviderStatus::Registered]);
    }
}
