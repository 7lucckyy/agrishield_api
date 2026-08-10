<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\Enums\MetricType;
use App\Enums\QualityFlag;
use Carbon\CarbonInterface;

final readonly class MetricReading
{
    /** @param array<string, mixed>|null $statistics */
    public function __construct(
        public MetricType $metricType,
        public ?float $value,
        public ?string $unit,
        public CarbonInterface $capturedAt,
        public ?int $resolutionMeters = null,
        public ?array $statistics = null,
        public ?string $mapUrl = null,
        public QualityFlag $quality = QualityFlag::Good,
        public ?string $externalReference = null,
    ) {}
}
