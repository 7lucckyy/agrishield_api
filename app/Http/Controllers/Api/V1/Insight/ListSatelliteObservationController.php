<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Insight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Insight\ListSatelliteObservationRequest;
use App\Http\Resources\Api\V1\SatelliteObservationResource;
use App\Models\Farm;
use App\Models\SatelliteObservation;
use App\Services\Insights\FreshnessCalculator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListSatelliteObservationController extends Controller
{
    public function __invoke(ListSatelliteObservationRequest $request, Farm $farm, FreshnessCalculator $freshnessCalculator): AnonymousResourceCollection
    {
        $query = SatelliteObservation::query()->whereBelongsTo($farm);
        $metricTypes = $request->metricTypes();
        $query->when($metricTypes !== [], fn ($builder) => $builder->whereIn('metric_type', $metricTypes));
        $query->when($request->validated('filter.from'), fn ($builder, $date) => $builder->whereDate('captured_at', '>=', $date));
        $query->when($request->validated('filter.to'), fn ($builder, $date) => $builder->whereDate('captured_at', '<=', $date));
        $query->when($request->validated('filter.quality'), fn ($builder, $quality) => $builder->where('quality_flag', $quality));
        $query->when($request->validated('filter.crop_cycle_id'), fn ($builder, $cycle) => $builder->where('farm_crop_cycle_id', $cycle));
        if ($request->boolean('latest_only')) {
            $query->whereIn('id', SatelliteObservation::query()
                ->whereBelongsTo($farm)
                ->selectRaw('MAX(id)')
                ->groupBy('metric_type'));
        }
        $direction = $request->validated('sort', '-captured_at') === 'captured_at' ? 'asc' : 'desc';
        $paginator = $query->orderBy('captured_at', $direction)->orderBy('id')->paginate((int) $request->validated('per_page', 25));
        $latest = SatelliteObservation::query()->whereBelongsTo($farm)->latest('captured_at')->first();
        $freshnessKey = match ($latest?->metric_type?->value) {
            'lswi' => 'lswi',
            'soil_moisture' => 'soil_moisture',
            default => 'ndvi',
        };

        return SatelliteObservationResource::collection($paginator)->additional(['meta' => [
            'state' => $latest === null ? 'pending_first_sync' : 'ready',
            'freshness' => $freshnessCalculator->calculate($freshnessKey, $latest?->captured_at, $latest?->fetched_at),
            'metrics_available' => SatelliteObservation::query()->whereBelongsTo($farm)->distinct()->pluck('metric_type')->all(),
        ]]);
    }
}
