<?php

declare(strict_types=1);

namespace App\DTOs\Provider;

final readonly class AdvisoryCollection
{
    /** @param list<AdvisoryItem> $items */
    public function __construct(
        public string $farmReference,
        public array $items,
        public string $source,
        public ?string $providerRequestId = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
