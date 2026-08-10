<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use Carbon\CarbonInterface;

final readonly class WeatherData
{
    /** @param list<ForecastDay> $days */
    public function __construct(
        public string $farmReference,
        public array $days,
        public CarbonInterface $fetchedAt,
        public string $source,
        public ?string $providerRequestId = null,
    ) {}
}
