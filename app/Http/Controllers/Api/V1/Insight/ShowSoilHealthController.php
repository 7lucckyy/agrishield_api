<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Insight;

use App\Enums\MetricType;
use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\SatelliteObservation;
use App\Services\Insights\FreshnessCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowSoilHealthController extends Controller
{
    public function __invoke(Request $request, Farm $farm, FreshnessCalculator $freshnessCalculator): JsonResponse
    {
        Gate::authorize('view', $farm);
        $metricTypes = [MetricType::SoilNitrogen, MetricType::SoilPhosphorus, MetricType::SoilPotassium, MetricType::SoilOrganicCarbon, MetricType::SoilPh];
        $history = $request->boolean('history');
        $observations = SatelliteObservation::query()
            ->whereBelongsTo($farm)
            ->whereIn('metric_type', $metricTypes)
            ->when($request->date('filter.from'), fn ($query, $date) => $query->where('captured_at', '>=', $date))
            ->when($request->date('filter.to'), fn ($query, $date) => $query->where('captured_at', '<=', $date->endOfDay()))
            ->latest('captured_at')
            ->get();

        if (! $history) {
            $observations = $observations->unique(fn (SatelliteObservation $observation): string => $observation->metric_type->value)->values();
        }

        $latest = $observations->first();

        return response()->json([
            'data' => [
                'farm_id' => $farm->uuid,
                'metrics' => $observations->map(fn (SatelliteObservation $observation): array => [
                    'metric_type' => $observation->metric_type->value,
                    'label' => $this->label($observation->metric_type),
                    'value' => $observation->value === null ? null : (float) $observation->value,
                    'unit' => $observation->unit,
                    'resolution_meters' => $observation->resolution_meters,
                    'captured_at' => $observation->captured_at->toISOString(),
                    'quality_flag' => $observation->quality_flag?->value,
                    'statistics' => $observation->statistics ?? [],
                ]),
                'related_advisories' => [],
            ],
            'meta' => [
                'state' => $latest === null ? 'pending_first_sync' : 'ready',
                'freshness' => $freshnessCalculator->calculate('soil_health', $latest?->captured_at, $latest?->fetched_at),
                'note' => 'Soil health is measured once per season, when the field is barren.',
            ],
        ]);
    }

    private function label(MetricType $metricType): string
    {
        return match ($metricType) {
            MetricType::SoilNitrogen => 'Nitrogen (N)',
            MetricType::SoilPhosphorus => 'Phosphorus (P)',
            MetricType::SoilPotassium => 'Potassium (K)',
            MetricType::SoilOrganicCarbon => 'Soil Organic Carbon',
            MetricType::SoilPh => 'pH',
            default => $metricType->value,
        };
    }
}
