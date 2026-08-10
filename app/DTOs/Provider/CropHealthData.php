<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use Carbon\CarbonInterface;

final readonly class CropHealthData
{
    /** @param list<MetricReading> $readings */
    public function __construct(
        public string $farmReference,
        public array $readings,
        public ?CarbonInterface $capturedAt,
        public string $source,
        public ?string $providerRequestId = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->readings === [];
    }
}
