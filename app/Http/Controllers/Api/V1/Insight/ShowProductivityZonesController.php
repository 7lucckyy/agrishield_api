<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Insight;

use App\Enums\FarmSectionStatus;
use App\Enums\MetricType;
use App\Enums\QualityFlag;
use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\SatelliteObservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowProductivityZonesController extends Controller
{
    public function __invoke(Request $request, Farm $farm): JsonResponse
    {
        Gate::authorize('view', $farm);

        $sections = $farm->sections()->where('status', FarmSectionStatus::Active)->whereNotNull('boundary_geojson')->get();
        $observations = SatelliteObservation::query()
            ->whereBelongsTo($farm)
            ->whereIn('farm_section_id', $sections->modelKeys())
            ->where('metric_type', MetricType::Ndvi)
            ->where('quality_flag', QualityFlag::Good)
            ->where('captured_at', '>=', now()->subDays(12))
            ->whereIn('source', ['sentinel2', 'landsat8', 'landsat9', 'satyukt', 'copernicus'])
            ->orderByDesc('captured_at')->get()->unique('farm_section_id')->keyBy('farm_section_id');

        $groups = $sections->groupBy('crop_id')->map(function ($cropSections) use ($observations): array {
            $ready = $cropSections->filter(fn ($section): bool => $observations->has($section->getKey()));
            if ($ready->count() < 3 || $ready->count() !== $cropSections->count()) {
                return ['state' => 'insufficient_data', 'zones' => []];
            }

            $timestamps = $ready->map(fn ($section): int => $observations->get($section->getKey())->captured_at->timestamp);
            if ($timestamps->max() - $timestamps->min() > 3 * 86400) {
                return ['state' => 'non_comparable_dates', 'zones' => []];
            }

            $sources = $ready->map(fn ($section): string => $observations->get($section->getKey())->source)->unique();
            $resolutions = $ready->map(fn ($section): ?int => $observations->get($section->getKey())->resolution_meters === null
                ? null : (int) $observations->get($section->getKey())->resolution_meters)->unique();
            if ($sources->count() !== 1 || $resolutions->count() !== 1) {
                return ['state' => 'non_comparable_sources', 'zones' => []];
            }

            $values = $ready->map(fn ($section): float => (float) $observations->get($section->getKey())->value)->sort()->values();
            if ($values->last() - $values->first() < 0.05) {
                return ['state' => 'no_meaningful_variation', 'zones' => []];
            }

            $low = $values->get((int) floor(($values->count() - 1) / 3));
            $high = $values->get((int) ceil(2 * ($values->count() - 1) / 3));
            $zones = [];
            foreach ($ready as $section) {
                $observation = $observations->get($section->getKey());
                $value = (float) $observation->value;

                $zones[] = [
                    'section_id' => $section->getKey(),
                    'section_name' => $section->name,
                    'boundary_geojson' => $section->boundary_geojson,
                    'ndvi' => $value,
                    'zone' => $value <= $low ? 'lower_relative_vigor' : ($value >= $high ? 'higher_relative_vigor' : 'middle_relative_vigor'),
                    'captured_at' => $observation->captured_at->toISOString(),
                    'source' => $observation->source,
                    'resolution_meters' => $observation->resolution_meters,
                ];
            }

            return ['state' => 'ready', 'zones' => $zones];
        });

        return response()->json(['data' => [
            'method' => 'relative_section_ndvi',
            'notice' => 'Relative vegetation vigor only. Confirm in the field before changing inputs; this is not a yield or fertilizer prescription.',
            'crop_groups' => $groups,
        ]]);
    }
}
