<?php

declare(strict_types=1);

namespace App\Services\Insights;

use Carbon\CarbonInterface;

final class FreshnessCalculator
{
    /**
     * @return array{observed_at: string|null, fetched_at: string|null, next_expected_update_at: string|null, is_stale: bool, cadence_days: int}
     */
    public function calculate(string $type, ?CarbonInterface $observedAt, ?CarbonInterface $fetchedAt): array
    {
        /** @var array{cadence?: int, stale_after?: int} $configuration */
        $configuration = config("farming.freshness.{$type}", []);
        $cadenceDays = (int) ($configuration['cadence'] ?? 1);
        $staleAfterDays = (int) ($configuration['stale_after'] ?? $cadenceDays * 2);
        $reference = $observedAt ?? $fetchedAt;

        return [
            'observed_at' => $observedAt?->toISOString(),
            'fetched_at' => $fetchedAt?->toISOString(),
            'next_expected_update_at' => $reference?->copy()->addDays($cadenceDays)->toISOString(),
            'is_stale' => $reference === null || $reference->copy()->addDays($staleAfterDays)->isPast(),
            'cadence_days' => $cadenceDays,
        ];
    }
}
