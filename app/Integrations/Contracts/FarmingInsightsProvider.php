<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\DTOs\Provider\AdvisoryCollection;
use App\DTOs\Provider\CropHealthData;
use App\DTOs\Provider\ProviderFarmReference;
use App\DTOs\Provider\ProviderHealth;
use App\DTOs\Provider\SoilHealthData;
use App\DTOs\Provider\SoilMoistureData;
use App\DTOs\Provider\WaterStressData;
use App\DTOs\Provider\WeatherData;
use App\Models\CropCycle;
use App\Models\Farm;
use Carbon\CarbonInterface;

interface FarmingInsightsProvider extends CropDiagnosisProvider
{
    public function name(): string;

    public function registerFarm(Farm $farm, string $idempotencyKey): ProviderFarmReference;

    public function registerCropCycle(CropCycle $cycle): ProviderFarmReference;

    public function fetchSoilHealth(Farm $farm, ?CarbonInterface $since = null): SoilHealthData;

    public function fetchWeather(Farm $farm): WeatherData;

    public function fetchCropHealth(Farm $farm, ?CarbonInterface $since = null): CropHealthData;

    public function fetchWaterStress(Farm $farm, ?CarbonInterface $since = null): WaterStressData;

    public function fetchSoilMoisture(Farm $farm, ?CarbonInterface $since = null): SoilMoistureData;

    public function fetchIrrigationAdvisory(Farm $farm): AdvisoryCollection;

    public function fetchPestForewarning(Farm $farm): AdvisoryCollection;

    public function fetchCropPractices(CropCycle $cycle): AdvisoryCollection;

    public function healthCheck(): ProviderHealth;
}
