<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Models\Farm;
use Illuminate\Database\Eloquent\Builder;

final class CalculateFarmProfitability
{
    /** @return array<string, mixed> */
    public function forFarm(Farm $farm, ?int $cropCycleId = null): array
    {
        $entries = $farm->ledgerEntries()
            ->when($cropCycleId !== null, fn (Builder $query): Builder => $query->where('farm_crop_cycle_id', $cropCycleId));

        $totals = (clone $entries)
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'income' THEN amount ELSE 0 END), 0) AS income")
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'expense' THEN amount ELSE 0 END), 0) AS expenses")
            ->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'harvest' THEN quantity ELSE 0 END), 0) AS harvest_quantity")
            ->firstOrFail();

        $income = (float) $totals->getAttribute('income');
        $expenses = (float) $totals->getAttribute('expenses');
        $profit = $income - $expenses;

        return [
            'currency' => 'NGN',
            'income' => round($income, 2),
            'expenses' => round($expenses, 2),
            'gross_profit' => round($profit, 2),
            'gross_margin_percent' => $income > 0 ? round(($profit / $income) * 100, 1) : null,
            'harvest_quantity' => (float) $totals->getAttribute('harvest_quantity'),
            'entry_count' => (clone $entries)->count(),
            'is_complete' => $income > 0 && $expenses > 0,
            'notice' => 'Profitability is based only on entries recorded for this farm and is not an audited financial statement.',
        ];
    }
}
