<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use Carbon\CarbonInterface;

final readonly class ProviderHealth
{
    public function __construct(
        public bool $reachable,
        public int $latencyMs,
        public CarbonInterface $checkedAt,
        public ?string $note = null,
    ) {}
}
