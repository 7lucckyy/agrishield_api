<?php

declare(strict_types=1);

namespace App\Data;

final readonly class AgronomyAdviceResult
{
    /** @param list<string> $nextSteps */
    public function __construct(
        public string $answer,
        public array $nextSteps,
        public string $uncertainty,
        public string $safetyNote,
        public ?string $providerReference = null,
    ) {}
}
