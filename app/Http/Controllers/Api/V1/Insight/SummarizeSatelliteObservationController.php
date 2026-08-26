<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Insight;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\SatelliteObservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SummarizeSatelliteObservationController extends Controller
{
    public function __invoke(Request $request, Farm $farm): JsonResponse
    {
        Gate::authorize('view', $farm);
        $from = $request->date('filter.from') ?? today()->subMonths(3);
        $to = $request->date('filter.to') ?? today();
        $observations = SatelliteObservation::query()
            ->whereBelongsTo($farm)
            ->whereBetween('captured_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderByDesc('captured_at')
            ->get();
        $series = [];
        foreach ($observations as $observation) {
            $series[$observation->metric_type->value][] = [
                'captured_at' => $observation->captured_at->toISOString(),
                'value' => $observation->value === null ? null : (float) $observation->value,
            ];
        }

        return response()->json([
            'data' => $series,
            'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'point_count' => $observations->count()],
        ]);
    }
}
