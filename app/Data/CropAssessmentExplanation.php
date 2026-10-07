<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CropAssessmentExplanation
{
    public function __construct(
        public string $diagnosis,
        public string $recommendation,
        public ?string $providerRequestId = null,
    ) {}
}