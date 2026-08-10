<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use Carbon\CarbonInterface;

final readonly class ForecastDay
{
    /** @param list<array<string, mixed>>|null $hourly */
    public function __construct(
        public CarbonInterface $date,
        public ?float $temperatureMin = null,
        public ?float $temperatureMax = null,
        public ?float $humidity = null,
        public ?float $rainfall = null,
        public ?float $rainfallProbability = null,
        public ?float $windSpeed = null,
        public ?float $windDirection = null,
        public ?float $cloudCover = null,
        public ?string $conditionCode = null,
        public ?array $hourly = null,
    ) {}
}
