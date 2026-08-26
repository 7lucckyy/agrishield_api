<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Farm;
use App\Models\WeatherForecast;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WeatherForecast> */
class WeatherForecastFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'forecast_date' => fake()->dateTimeBetween('today', '+14 days'),
            'temperature_min' => fake()->randomFloat(2, 18, 26),
            'temperature_max' => fake()->randomFloat(2, 27, 39),
            'humidity' => fake()->randomFloat(2, 30, 95),
            'rainfall' => fake()->randomFloat(2, 0, 30),
            'rainfall_probability' => fake()->randomFloat(2, 0, 100),
            'wind_speed' => fake()->randomFloat(2, 0, 15),
            'cloud_cover' => fake()->randomFloat(2, 0, 100),
            'condition_code' => 'partly_cloudy',
            'source' => 'fake',
            'fetched_at' => now(),
        ];
    }
}
