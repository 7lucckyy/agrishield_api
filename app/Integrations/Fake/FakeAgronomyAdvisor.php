<?php

declare(strict_types=1);

namespace App\Integrations\Fake;

use App\Data\AgronomyAdviceResult;
use App\Integrations\Contracts\AgronomyAdvisor;

final class FakeAgronomyAdvisor implements AgronomyAdvisor
{
    public function advise(string $question, string $language, array $context): AgronomyAdviceResult
    {
        return new AgronomyAdviceResult(
            'Inspect the field and check local conditions before changing your crop plan.',
            ['Inspect the affected area', 'Record what you see and when it started', 'Ask a local extension worker before treatment'],
            'This demonstration has no verified field diagnosis.',
            'Do not apply a chemical treatment based only on this answer.',
        );
    }

    public function name(): string
    {
        return 'fake';
    }
}
