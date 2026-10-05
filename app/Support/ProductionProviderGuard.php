<?php

declare(strict_types=1);

namespace App\Support;

use LogicException;

final class ProductionProviderGuard
{
    /** @param array<string, mixed> $providers */
    public function ensureSafe(string $environment, array $providers): void
    {
        if ($environment !== 'production') {
            return;
        }

        $fakeProviders = collect($providers)
            ->filter(fn (mixed $provider): bool => $provider === 'fake')
            ->keys()
            ->implode(', ');

        if ($fakeProviders !== '') {
            throw new LogicException("Production cannot start with fake providers configured: {$fakeProviders}.");
        }
    }
}
