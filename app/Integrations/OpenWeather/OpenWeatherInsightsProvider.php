<?php

declare(strict_types=1);

namespace App\Integrations\OpenWeather;

use App\DTOs\Provider\AdvisoryCollection;
use App\DTOs\Provider\CropHealthData;
use App\DTOs\Provider\DiagnosisResult;
use App\DTOs\Provider\DiagnosisSubmission;
use App\DTOs\Provider\ForecastDay;
use App\DTOs\Provider\ProviderFarmReference;
use App\DTOs\Provider\ProviderHealth;
use App\DTOs\Provider\SoilHealthData;
use App\DTOs\Provider\SoilMoistureData;
use App\DTOs\Provider\WaterStressData;
use App\DTOs\Provider\WeatherData;
use App\Exceptions\Provider\ProviderAuthFailed;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Exceptions\Provider\ProviderNotImplemented;
use App\Exceptions\Provider\ProviderRateLimited;
use App\Exceptions\Provider\ProviderTimeout;
use App\Exceptions\Provider\ProviderUnavailable;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\CropCycle;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class OpenWeatherInsightsProvider implements FarmingInsightsProvider
{
    public function name(): string
    {
        return 'openweather';
    }

    public function fetchWeather(Farm $farm): WeatherData
    {
        $latitude = (float) $farm->centroid_latitude;
        $longitude = (float) $farm->centroid_longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new ProviderContractViolation('The farm coordinates are invalid for weather lookup.', 'invalid_farm_coordinates');
        }

        $cacheKey = sprintf('openweather:forecast:%s:%0.4F:%0.4F', $farm->uuid, $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addSeconds(max(60, (int) config('farming.providers.openweather.cache_seconds'))), function () use ($farm, $latitude, $longitude): WeatherData {
            $apiKey = (string) config('farming.providers.openweather.api_key');
            if ($apiKey === '') {
                throw new ProviderAuthFailed('OpenWeather is not configured.', 'openweather_not_configured');
            }

            try {
                $response = Http::baseUrl(rtrim((string) config('farming.providers.openweather.base_url'), '/'))
                    ->acceptJson()
                    ->connectTimeout((int) config('farming.providers.openweather.connect_timeout'))
                    ->timeout((int) config('farming.providers.openweather.read_timeout'))
                    ->retry([200, 500], throw: false)
                    ->get('/forecast', [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'units' => 'metric',
                        'appid' => $apiKey,
                    ]);
            } catch (ConnectionException $exception) {
                throw new ProviderTimeout('OpenWeather did not respond in time.', 'openweather_timeout', retryable: true, previous: $exception);
            }

            if ($response->status() === 401 || $response->status() === 403) {
                throw new ProviderAuthFailed('OpenWeather rejected the configured credentials.', 'openweather_auth_failed', $response->header('X-Request-Id'));
            }
            if ($response->status() === 429) {
                throw new ProviderRateLimited('OpenWeather rate limit reached.', 'openweather_rate_limited', $response->header('X-Request-Id'), true);
            }
            if ($response->serverError()) {
                throw new ProviderUnavailable('OpenWeather is temporarily unavailable.', 'openweather_unavailable', $response->header('X-Request-Id'), true);
            }
            if ($response->failed()) {
                throw new ProviderContractViolation('OpenWeather rejected the weather request.', 'openweather_request_rejected', $response->header('X-Request-Id'));
            }

            $items = $response->json('list');
            if (! is_array($items) || $items === []) {
                throw new ProviderContractViolation('OpenWeather returned no forecast entries.', 'openweather_invalid_response', $response->header('X-Request-Id'));
            }

            $days = [];
            foreach ($items as $item) {
                if (! is_array($item) || ! is_numeric($item['dt'] ?? null) || ! is_array($item['main'] ?? null)) {
                    continue;
                }

                $date = CarbonImmutable::createFromTimestampUTC((int) $item['dt']);
                $key = $date->toDateString();
                $day = $days[$key] ?? ['date' => $date->startOfDay(), 'temperatures' => [], 'humidity' => [], 'rainfall' => 0.0, 'rainfall_probability' => [], 'wind_speed' => [], 'wind_direction' => [], 'cloud_cover' => [], 'hourly' => []];
                $day['temperatures'][] = (float) ($item['main']['temp'] ?? 0);
                $day['humidity'][] = isset($item['main']['humidity']) ? (float) $item['main']['humidity'] : null;
                $day['rainfall'] += (float) ($item['rain']['3h'] ?? $item['snow']['3h'] ?? 0);
                $day['rainfall_probability'][] = isset($item['pop']) ? (float) $item['pop'] * 100 : null;
                $day['wind_speed'][] = isset($item['wind']['speed']) ? (float) $item['wind']['speed'] : null;
                $day['wind_direction'][] = isset($item['wind']['deg']) ? (float) $item['wind']['deg'] : null;
                $day['cloud_cover'][] = isset($item['clouds']['all']) ? (float) $item['clouds']['all'] : null;
                $day['condition_code'] = (string) ($item['weather'][0]['id'] ?? 'unknown');
                $day['hourly'][] = $item;
                $days[$key] = $day;
            }

            $forecast = array_map(static fn (array $day): ForecastDay => new ForecastDay(
                date: $day['date'],
                temperatureMin: min($day['temperatures']),
                temperatureMax: max($day['temperatures']),
                humidity: self::average($day['humidity']),
                rainfall: $day['rainfall'],
                rainfallProbability: self::average($day['rainfall_probability']),
                windSpeed: self::average($day['wind_speed']),
                windDirection: self::average($day['wind_direction']),
                cloudCover: self::average($day['cloud_cover']),
                conditionCode: $day['condition_code'] ?? null,
                hourly: $day['hourly'],
            ), $days);

            if ($forecast === []) {
                throw new ProviderContractViolation('OpenWeather returned malformed forecast entries.', 'openweather_invalid_response', $response->header('X-Request-Id'));
            }

            return new WeatherData($farm->uuid, array_values($forecast), now(), $this->name(), $response->header('X-Request-Id'));
        });
    }

    public function registerFarm(Farm $farm, string $idempotencyKey): ProviderFarmReference { $this->unsupported('farm registration'); }
    public function registerCropCycle(CropCycle $cycle): ProviderFarmReference { $this->unsupported('crop-cycle registration'); }
    public function fetchSoilHealth(Farm $farm, ?CarbonInterface $since = null): SoilHealthData { $this->unsupported('soil health'); }
    public function fetchCropHealth(Farm $farm, ?CarbonInterface $since = null): CropHealthData { $this->unsupported('crop health'); }
    public function fetchWaterStress(Farm $farm, ?CarbonInterface $since = null): WaterStressData { $this->unsupported('water stress'); }
    public function fetchSoilMoisture(Farm $farm, ?CarbonInterface $since = null): SoilMoistureData { $this->unsupported('soil moisture'); }
    public function fetchIrrigationAdvisory(Farm $farm): AdvisoryCollection { $this->unsupported('irrigation advisories'); }
    public function fetchPestForewarning(Farm $farm): AdvisoryCollection { $this->unsupported('pest forewarning'); }
    public function fetchCropPractices(CropCycle $cycle): AdvisoryCollection { $this->unsupported('crop practices'); }
    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission { $this->unsupported('diagnosis submission'); }
    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult { $this->unsupported('diagnosis results'); }
    public function healthCheck(): ProviderHealth { return new ProviderHealth(true, 0, now(), 'OpenWeather is configured.'); }

    /** @param list<float|null> $values */
    private static function average(array $values): ?float
    {
        $values = array_values(array_filter($values, static fn (?float $value): bool => $value !== null));

        return $values === [] ? null : array_sum($values) / count($values);
    }

    private function unsupported(string $capability): never
    {
        throw new ProviderNotImplemented("OpenWeather does not provide {$capability}.", 'provider_capability_not_supported');
    }
}
