<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use App\Enums\AdvisorySeverity;
use App\Enums\AdvisoryType;
use Carbon\CarbonInterface;

final readonly class AdvisoryItem
{
    /** @param array<string, mixed>|null $payload */
    public function __construct(
        public AdvisoryType $type,
        public string $title,
        public ?string $summary,
        public ?AdvisorySeverity $severity,
        public ?array $payload,
        public ?CarbonInterface $observedAt,
        public ?CarbonInterface $validFrom,
        public ?CarbonInterface $validUntil,
        public ?string $externalReference = null,
    ) {}
}
