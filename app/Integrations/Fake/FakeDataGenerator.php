<?php

declare(strict_types=1);

namespace App\Integrations\Fake;

use App\DTOs\Provider\ForecastDay;
use App\DTOs\Provider\MetricReading;
use App\Enums\MetricType;
use App\Enums\QualityFlag;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class FakeDataGenerator
{
    /**
     * @return list<MetricReading>
     */
    public function seasonalIndexSeries(
        int $seed,
        MetricType $metric,
        CarbonInterface $since,
        int $cadenceDays,
        int $resolutionMeters,
        float $cloudProbability,
    ): array {
        $cursor = CarbonImmutable::instance($since)->startOfDay();
        $today = CarbonImmutable::today();
        $readings = [];

        while ($cursor->lte($today)) {
            $cloudRatio = $this->ratio($seed, $metric->value.'|cloud|'.$cursor->toDateString());
            if ($cloudRatio >= $cloudProbability) {
                $seasonal = sin((($cursor->dayOfYear - 30) / 365) * 2 * M_PI);
                $noise = ($this->ratio($seed, $metric->value.'|value|'.$cursor->toDateString()) - 0.5) * 0.08;
                $value = match ($metric) {
                    MetricType::Ndvi => max(-1.0, min(1.0, 0.35 + (0.4 * $seasonal) + $noise)),
                    MetricType::Lswi => max(-1.0, min(1.0, 0.05 + (0.3 * $seasonal) + $noise)),
                    MetricType::SoilMoisture => max(0.0, min(1.0, 0.4 + (0.2 * $seasonal) + $noise)),
                    default => max(0.0, 1.0 + $seasonal + $noise),
                };

                $readings[] = new MetricReading(
                    metricType: $metric,
                    value: round($value, 4),
                    unit: $metric === MetricType::SoilMoisture ? 'fraction' : 'index',
                    capturedAt: $cursor,
                    resolutionMeters: $resolutionMeters,
                    quality: $cloudRatio < $cloudProbability + 0.1
                        ? QualityFlag::PartialCloud
                        : QualityFlag::Good,
                    externalReference: 'fake-'.$metric->value.'-'.$cursor->format('Ymd'),
                );
            }

            $cursor = $cursor->addDays($cadenceDays);
        }

        return $readings;
    }

    /** @return list<ForecastDay> */
    public function weatherForecast(int $seed, CarbonInterface $from, int $days = 15): array
    {
        $forecast = [];
        $start = CarbonImmutable::instance($from)->startOfDay();

        for ($offset = 0; $offset < min($days, 15); $offset++) {
            $date = $start->addDays($offset);
            $heat = $this->ratio($seed, 'weather|heat|'.$date->toDateString());
            $rain = $this->ratio($seed, 'weather|rain|'.$date->toDateString());

            $forecast[] = new ForecastDay(
                date: $date,
                temperatureMin: round(19 + (7 * $heat), 1),
                temperatureMax: round(29 + (9 * $heat), 1),
                humidity: round(45 + (45 * $rain), 1),
                rainfall: $rain > 0.62 ? round(30 * $rain, 1) : 0.0,
                rainfallProbability: round($rain, 2),
                windSpeed: round(2 + (8 * $this->ratio($seed, 'weather|wind|'.$date->toDateString())), 1),
                cloudCover: round(100 * $rain, 1),
                conditionCode: $rain > 0.62 ? 'rain' : 'partly_cloudy',
            );
        }

        return $forecast;
    }

    /** @return list<MetricReading> */
    public function soilHealth(int $seed, CarbonInterface $capturedAt): array
    {
        $metrics = [
            [MetricType::SoilNitrogen, 'ppm', 15.0, 60.0],
            [MetricType::SoilPhosphorus, 'ppm', 8.0, 40.0],
            [MetricType::SoilPotassium, 'ppm', 60.0, 240.0],
            [MetricType::SoilOrganicCarbon, 'percent', 0.5, 3.5],
            [MetricType::SoilPh, 'pH', 5.2, 7.8],
        ];
        $readings = [];

        foreach ($metrics as [$metric, $unit, $minimum, $maximum]) {
            $value = $minimum + (($maximum - $minimum) * $this->ratio($seed, 'soil|'.$metric->value));
            $readings[] = new MetricReading(
                metricType: $metric,
                value: round($value, 4),
                unit: $unit,
                capturedAt: CarbonImmutable::instance($capturedAt),
                resolutionMeters: 10,
                externalReference: 'fake-soil-'.$metric->value.'-'.$capturedAt->format('Ymd'),
            );
        }

        return $readings;
    }

    private function ratio(int $seed, string $key): float
    {
        $integer = hexdec(mb_substr(hash('sha256', $seed.'|'.$key), 0, 8));

        return $integer / 4294967295;
    }
}
