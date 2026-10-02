<?php

declare(strict_types=1);

namespace App\Actions\Farm;

use App\Data\Farm\FarmListResult;
use App\Enums\FarmStatus;
use App\Enums\ProviderStatus;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

final class ListFarms
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array{field: string, direction: 'asc'|'desc'}  $sort
     */
    public function execute(
        User $user,
        array $filters,
        array $sort,
        int $perPage,
        string $path,
    ): FarmListResult {
        $query = Farm::query()
            ->visibleTo($user)
            ->with(['owner:id,name', 'organization:id,name', 'activeCropCycle.farm:id,uuid', 'activeCropCycle.crop']);

        $this->applyFilters($query, $filters);

        $aggregate = (clone $query)
            ->withoutEagerLoads()
            ->selectRaw('COUNT(*) AS farms_count, COALESCE(SUM(area_hectares), 0) AS hectares, COALESCE(SUM(area_acres), 0) AS acres')
            ->first();

        $farms = $query
            ->withCount('sections')
            ->orderBy($sort['field'], $sort['direction'])
            ->orderBy('id')
            ->paginate($perPage)
            ->withPath($path)
            ->withQueryString();

        return new FarmListResult($farms, [
            'farms' => (int) ($aggregate?->getAttribute('farms_count') ?? 0),
            'hectares' => (float) ($aggregate?->getAttribute('hectares') ?? 0),
            'acres' => (float) ($aggregate?->getAttribute('acres') ?? 0),
        ]);
    }

    /**
     * @param  Builder<Farm>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $organizationId = Arr::get($filters, 'organization_id');
        if (is_int($organizationId)) {
            $query->where('organization_id', $organizationId);
        }

        $status = Arr::get($filters, 'status');
        if ($status instanceof FarmStatus) {
            $query->where('status', $status);
        }

        $providerStatus = Arr::get($filters, 'provider_status');
        if ($providerStatus instanceof ProviderStatus) {
            $query->where('provider_status', $providerStatus);
        }

        $cropId = Arr::get($filters, 'crop_id');
        if (is_int($cropId)) {
            $query->whereHas('cropCycles', fn (Builder $cycles): Builder => $cycles->where('crop_id', $cropId));
        }

        $search = Arr::get($filters, 'search');
        if (is_string($search)) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(locality) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(state) LIKE ?', ['%'.mb_strtolower($search).'%']);
            });
        }
    }
}
