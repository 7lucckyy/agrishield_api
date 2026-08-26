<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Enums\AdvisorySeverity;
use App\Enums\CropCycleStatus;
use App\Enums\FarmStatus;
use App\Enums\MetricType;
use App\Enums\ProviderStatus;
use App\Enums\SyncStatus;
use App\Models\Advisory;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Builder;

final class GetOrganizationOverview
{
    /** @return array<string, mixed> */
    public function execute(Organization $organization): array
    {
        $farms = Farm::query()->whereBelongsTo($organization);
        $area = (clone $farms)->selectRaw('COALESCE(SUM(area_hectares), 0) AS hectares, COALESCE(SUM(area_acres), 0) AS acres')->first();
        $advisoryCounts = Advisory::query()
            ->whereHas('farm', fn (Builder $query): Builder => $query->whereBelongsTo($organization))
            ->where('observed_at', '>=', now()->subDays(7))
            ->selectRaw('severity, COUNT(*) AS aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        return [
            'farms' => [
                'total' => (clone $farms)->count(),
                'active' => (clone $farms)->where('status', FarmStatus::Active)->count(),
                'pending_registration' => (clone $farms)->where('provider_status', ProviderStatus::Pending)->count(),
                'failed_registration' => (clone $farms)->where('provider_status', ProviderStatus::Failed)->count(),
            ],
            'area' => [
                'hectares' => (float) ($area?->getAttribute('hectares') ?? 0),
                'acres' => (float) ($area?->getAttribute('acres') ?? 0),
            ],
            'crops' => $this->cropBreakdown($organization),
            'advisories_last_7_days' => collect(AdvisorySeverity::cases())->mapWithKeys(
                fn (AdvisorySeverity $severity): array => [$severity->value => (int) ($advisoryCounts[$severity->value] ?? 0)],
            ),
            'data_health' => [
                'farms_with_stale_ndvi' => (clone $farms)->whereDoesntHave('satelliteObservations', fn (Builder $query): Builder => $query
                    ->where('metric_type', MetricType::Ndvi)
                    ->where('next_expected_update_at', '>', now()))->count(),
                'farms_never_synced' => (clone $farms)->whereNull('last_synced_at')->count(),
                'last_successful_sync_at' => SyncRun::query()
                    ->whereHas('farm', fn (Builder $query): Builder => $query->whereBelongsTo($organization))
                    ->where('status', SyncStatus::Succeeded)
                    ->max('completed_at'),
            ],
            'diagnosis' => ['pending' => 0, 'completed_last_30_days' => 0],
        ];
    }

    /** @return list<array{crop_id: int, name: string, farms: int, hectares: float}> */
    private function cropBreakdown(Organization $organization): array
    {
        $cycles = CropCycle::query()
            ->where('status', CropCycleStatus::Active)
            ->whereHas('farm', fn (Builder $query): Builder => $query->whereBelongsTo($organization))
            ->with(['crop:id,name', 'farm:id,area_hectares'])
            ->get();

        return $cycles->groupBy('crop_id')->map(function ($group): array {
            $first = $group->first();

            return [
                'crop_id' => (int) $first->crop_id,
                'name' => $first->crop->name,
                'farms' => $group->pluck('farm_id')->unique()->count(),
                'hectares' => (float) $group->sum(fn (CropCycle $cycle): float => (float) $cycle->farm->area_hectares),
            ];
        })->values()->all();
    }
}
