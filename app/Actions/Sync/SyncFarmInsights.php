<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\DTOs\Provider\AdvisoryCollection;
use App\DTOs\Provider\MetricReading;
use App\Enums\MetricType;
use App\Enums\SyncType;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\Advisory;
use App\Models\Farm;
use App\Models\SatelliteObservation;
use App\Models\WeatherForecast;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

final class SyncFarmInsights
{
    public function execute(Farm $farm, SyncType $type, FarmingInsightsProvider $provider): int
    {
        return DB::transaction(fn (): int => match ($type) {
            SyncType::SoilHealth => $this->syncReadings(
                $farm,
                $provider->fetchSoilHealth($farm, $this->latestCapturedAt($farm, [
                    MetricType::SoilNitrogen,
                    MetricType::SoilPhosphorus,
                    MetricType::SoilPotassium,
                    MetricType::SoilOrganicCarbon,
                    MetricType::SoilPh,
                ]))->readings,
                $provider->name(),
            ),
            SyncType::Weather => $this->syncWeather($farm, $provider),
            SyncType::CropHealth => $this->syncReadings(
                $farm,
                $provider->fetchCropHealth($farm, $this->latestCapturedAt($farm, [MetricType::Ndvi]))->readings,
                $provider->name(),
            ),
            SyncType::WaterStress => $this->syncReadings(
                $farm,
                $provider->fetchWaterStress($farm, $this->latestCapturedAt($farm, [MetricType::Lswi]))->readings,
                $provider->name(),
            ),
            SyncType::SoilMoisture => $this->syncReadings(
                $farm,
                $provider->fetchSoilMoisture($farm, $this->latestCapturedAt($farm, [MetricType::SoilMoisture]))->readings,
                $provider->name(),
            ),
            SyncType::IrrigationAdvisory => $this->syncAdvisories($farm, $provider->fetchIrrigationAdvisory($farm)),
            SyncType::PestForewarning => $this->syncAdvisories($farm, $provider->fetchPestForewarning($farm)),
            SyncType::CropPractices => $farm->activeCropCycle === null
                ? 0
                : $this->syncAdvisories($farm, $provider->fetchCropPractices($farm->activeCropCycle), $farm->activeCropCycle->getKey()),
            default => throw new LogicException("{$type->value} is not an insight sync type."),
        });
    }

    /** @param list<MetricReading> $readings */
    private function syncReadings(Farm $farm, array $readings, string $source): int
    {
        $written = 0;
        $activeCropCycleId = $farm->activeCropCycle?->getKey();

        foreach ($readings as $reading) {
            $observation = SatelliteObservation::query()->firstOrNew([
                'farm_id' => $farm->getKey(),
                'metric_type' => $reading->metricType,
                'captured_at' => $reading->capturedAt,
                'source' => $source,
            ]);
            $wasRecentlyCreated = ! $observation->exists;
            $observation->farm()->associate($farm);
            $observation->fill([
                'farm_crop_cycle_id' => $activeCropCycleId,
                'value' => $reading->value,
                'unit' => $reading->unit,
                'map_url' => $reading->mapUrl,
                'statistics' => $reading->statistics,
                'resolution_meters' => $reading->resolutionMeters,
                'fetched_at' => now(),
                'next_expected_update_at' => $reading->capturedAt->copy()->addDays($this->cadenceDays($reading->metricType)),
                'quality_flag' => $reading->quality,
                'external_reference' => $reading->externalReference,
            ]);
            $observation->save();
            $written += (int) $wasRecentlyCreated;
        }

        return $written;
    }

    private function syncWeather(Farm $farm, FarmingInsightsProvider $provider): int
    {
        $weather = $provider->fetchWeather($farm);
        $written = 0;
        WeatherForecast::query()->whereBelongsTo($farm)->where('source', $weather->source)->delete();

        foreach ($weather->days as $day) {
            $date = $day->date->toDateString();
            $forecast = WeatherForecast::query()->firstOrNew([
                'farm_id' => $farm->getKey(),
                'source' => $weather->source,
                'forecast_date' => $date,
            ]);
            $wasRecentlyCreated = ! $forecast->exists;
            $forecast->farm()->associate($farm);
            $forecast->fill([
                'temperature_min' => $day->temperatureMin,
                'temperature_max' => $day->temperatureMax,
                'humidity' => $day->humidity,
                'rainfall' => $day->rainfall,
                'rainfall_probability' => $day->rainfallProbability,
                'wind_speed' => $day->windSpeed,
                'wind_direction' => $day->windDirection,
                'cloud_cover' => $day->cloudCover,
                'condition_code' => $day->conditionCode,
                'payload' => $day->hourly,
                'fetched_at' => $weather->fetchedAt,
            ]);
            $forecast->save();
            $written += (int) $wasRecentlyCreated;
        }

        return $written;
    }

    private function syncAdvisories(Farm $farm, AdvisoryCollection $collection, ?int $cropCycleId = null): int
    {
        $written = 0;

        foreach ($collection->items as $item) {
            $dedupeKey = hash('sha256', implode('|', [
                $farm->getKey(),
                $item->type->value,
                $item->observedAt?->toISOString() ?? '',
                $item->title,
                $collection->source,
            ]));
            $advisory = Advisory::query()->firstOrNew(['dedupe_key' => $dedupeKey]);
            $wasRecentlyCreated = ! $advisory->exists;
            $advisory->farm()->associate($farm);
            $advisory->fill([
                'farm_crop_cycle_id' => $cropCycleId,
                'type' => $item->type,
                'title' => $item->title,
                'summary' => $item->summary,
                'payload' => $item->payload,
                'severity' => $item->severity,
                'observed_at' => $item->observedAt,
                'valid_from' => $item->validFrom,
                'valid_until' => $item->validUntil,
                'source' => $collection->source,
                'external_reference' => $item->externalReference,
            ]);
            $advisory->save();
            $written += (int) $wasRecentlyCreated;
        }

        return $written;
    }

    /** @param list<MetricType> $metricTypes */
    private function latestCapturedAt(Farm $farm, array $metricTypes): ?CarbonInterface
    {
        $capturedAt = SatelliteObservation::query()
            ->whereBelongsTo($farm)
            ->whereIn('metric_type', $metricTypes)
            ->max('captured_at');

        return is_string($capturedAt) ? CarbonImmutable::parse($capturedAt) : null;
    }

    private function cadenceDays(MetricType $metricType): int
    {
        $key = match ($metricType) {
            MetricType::Ndvi => 'ndvi',
            MetricType::Lswi => 'lswi',
            MetricType::SoilMoisture => 'soil_moisture',
            default => 'soil_health',
        };

        return (int) config("farming.freshness.{$key}.cadence", 1);
    }
}
