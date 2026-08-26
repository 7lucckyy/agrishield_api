<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Insight;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\WeatherForecast;
use App\Services\Insights\FreshnessCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowWeatherController extends Controller
{
    public function __invoke(Request $request, Farm $farm, FreshnessCalculator $freshnessCalculator): JsonResponse
    {
        Gate::authorize('view', $farm);
        $from = $request->date('filter.from') ?? today();
        $to = $request->date('filter.to') ?? today()->addDays(14);
        $forecasts = WeatherForecast::query()
            ->whereBelongsTo($farm)
            ->whereDate('forecast_date', '>=', $from->toDateString())
            ->whereDate('forecast_date', '<=', $to->toDateString())
            ->orderBy('forecast_date')
            ->get();
        $latest = $forecasts->sortByDesc('fetched_at')->first();

        return response()->json([
            'data' => [
                'farm_id' => $farm->uuid,
                'location' => ['latitude' => (float) $farm->centroid_latitude, 'longitude' => (float) $farm->centroid_longitude],
                'days' => $forecasts->map(fn (WeatherForecast $forecast): array => [
                    'forecast_date' => $forecast->forecast_date->toDateString(),
                    'temperature_min' => $forecast->temperature_min === null ? null : (float) $forecast->temperature_min,
                    'temperature_max' => $forecast->temperature_max === null ? null : (float) $forecast->temperature_max,
                    'humidity' => $forecast->humidity === null ? null : (float) $forecast->humidity,
                    'rainfall' => $forecast->rainfall === null ? null : (float) $forecast->rainfall,
                    'rainfall_probability' => $forecast->rainfall_probability === null ? null : (float) $forecast->rainfall_probability,
                    'wind_speed' => $forecast->wind_speed === null ? null : (float) $forecast->wind_speed,
                    'wind_direction' => $forecast->wind_direction === null ? null : (float) $forecast->wind_direction,
                    'cloud_cover' => $forecast->cloud_cover === null ? null : (float) $forecast->cloud_cover,
                    'condition_code' => $forecast->condition_code,
                    'hourly' => $request->string('include')->toString() === 'hourly' ? $forecast->payload : null,
                ]),
            ],
            'meta' => [
                'state' => $latest === null ? 'pending_first_sync' : 'ready',
                'days_returned' => $forecasts->count(),
                'source' => $latest?->source,
                'freshness' => $freshnessCalculator->calculate('weather', null, $latest?->fetched_at),
            ],
        ]);
    }
}
