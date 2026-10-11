<?php

declare(strict_types=1);

namespace App\Integrations\Copernicus;

use App\DTOs\Provider\AdvisoryCollection;
use App\DTOs\Provider\CropHealthData;
use App\DTOs\Provider\DiagnosisResult;
use App\DTOs\Provider\DiagnosisSubmission;
use App\DTOs\Provider\ProviderFarmReference;
use App\DTOs\Provider\ProviderHealth;
use App\DTOs\Provider\SoilHealthData;
use App\DTOs\Provider\SoilMoistureData;
use App\DTOs\Provider\WaterStressData;
use App\DTOs\Provider\WeatherData;
use App\Exceptions\Provider\ProviderNotImplemented;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Integrations\OpenWeather\OpenWeatherInsightsProvider;
use App\Models\CropCycle;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Carbon\CarbonInterface;

final class CopernicusInsightsProvider implements FarmingInsightsProvider
{
    public function __construct(
        private readonly CopernicusNdviClient $ndvi,
        private readonly OpenWeatherInsightsProvider $weather,
    ) {}

    public function name(): string
    {
        return 'copernicus';
    }

    public function registerFarm(Farm $farm, string $idempotencyKey): ProviderFarmReference
    {
        $this->ndvi->accessToken();

        return new ProviderFarmReference($farm->uuid, now(), ['boundary_hash' => $farm->boundary_hash]);
    }

    public function registerCropCycle(CropCycle $cycle): ProviderFarmReference
    {
        return new ProviderFarmReference((string) $cycle->getKey(), now());
    }

    public function fetchCropHealth(Farm $farm, ?CarbonInterface $since = null): CropHealthData
    {
        $reading = $this->ndvi->latest($farm->boundary_geojson, $farm->uuid);

        return new CropHealthData($farm->uuid, $reading === null ? [] : [$reading], $reading?->capturedAt, $this->name());
    }

    public function fetchWeather(Farm $farm): WeatherData
    {
        return $this->weather->fetchWeather($farm);
    }

    public function fetchSoilHealth(Farm $farm, ?CarbonInterface $since = null): SoilHealthData
    {
        $this->unsupported('soil chemistry');
    }

    public function fetchWaterStress(Farm $farm, ?CarbonInterface $since = null): WaterStressData
    {
        $this->unsupported('water stress');
    }

    public function fetchSoilMoisture(Farm $farm, ?CarbonInterface $since = null): SoilMoistureData
    {
        $this->unsupported('soil moisture');
    }

    public function fetchIrrigationAdvisory(Farm $farm): AdvisoryCollection
    {
        $this->unsupported('irrigation advice');
    }

    public function fetchPestForewarning(Farm $farm): AdvisoryCollection
    {
        $this->unsupported('pest forecasting');
    }

    public function fetchCropPractices(CropCycle $cycle): AdvisoryCollection
    {
        $this->unsupported('crop practices');
    }

    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission
    {
        $this->unsupported('image diagnosis');
    }

    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult
    {
        $this->unsupported('image diagnosis');
    }

    public function healthCheck(): ProviderHealth
    {
        $started = microtime(true);
        $this->ndvi->accessToken();

        return new ProviderHealth(true, (int) round((microtime(true) - $started) * 1000), now(), 'Copernicus OAuth is reachable.');
    }

    private function unsupported(string $capability): never
    {
        throw new ProviderNotImplemented("Copernicus Sentinel-2 does not provide {$capability}.", 'provider_capability_not_supported');
    }
}
