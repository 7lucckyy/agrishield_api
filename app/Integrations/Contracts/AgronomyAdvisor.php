<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\Data\AgronomyAdviceResult;

interface AgronomyAdvisor
{
    /** @param array<string, mixed> $context */
    public function advise(string $question, string $language, array $context): AgronomyAdviceResult;

    public function name(): string;
}
