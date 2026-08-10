<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use Carbon\CarbonInterface;

final readonly class DiagnosisSubmission
{
    public function __construct(
        public string $providerCaseId,
        public CarbonInterface $submittedAt,
        public ?CarbonInterface $expectedBy = null,
        public ?string $providerRequestId = null,
    ) {}
}
