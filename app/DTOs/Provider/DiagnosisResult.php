<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\Enums\DiagnosisResultStatus;
use Carbon\CarbonInterface;

final readonly class DiagnosisResult
{
    /** @param list<array{label: string, confidence: float}>|null $detectedLabels */
    public function __construct(
        public DiagnosisResultStatus $status,
        public ?string $diagnosis = null,
        public ?string $recommendation = null,
        public ?float $confidence = null,
        public ?array $detectedLabels = null,
        public ?CarbonInterface $completedAt = null,
        public ?string $failureReason = null,
        public ?string $providerRequestId = null,
    ) {}
}
