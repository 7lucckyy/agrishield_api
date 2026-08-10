<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

use Carbon\CarbonInterface;

final readonly class ProviderFarmReference
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public string $providerFarmId,
        public CarbonInterface $registeredAt,
        public ?array $metadata = null,
    ) {}
}
