<?php

declare(strict_types=1);

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
use App\Enums\MetricType;
use App\Enums\QualityFlag;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Integrations\Fake\FakeInsightsProvider;
use App\Models\CropCycle;
use App\Models\DiagnosisRequest;
use App\Models\Farm;

function fakeContractProvider(): FarmingInsightsProvider
{
    return app(FakeInsightsProvider::class);
}

test('the configured provider resolves through the platform contract', function () {
    expect(app(FarmingInsightsProvider::class))
        ->toBeInstanceOf(FakeInsightsProvider::class)
        ->and(fakeContractProvider()->name())->toBe('fake');
});

test('provider farm registration is idempotent for a boundary hash', function () {
    $farm = Farm::factory()->create();

    $first = fakeContractProvider()->registerFarm($farm, $farm->boundary_hash);
    $second = fakeContractProvider()->registerFarm($farm, $farm->boundary_hash);

    expect($second->providerFarmId)->toBe($first->providerFarmId);
});

test('weather contract returns no more than fifteen typed forecast days', function () {
    $weather = fakeContractProvider()->fetchWeather(Farm::factory()->create());

    expect($weather->days)
        ->toHaveCount(15)
        ->each->toBeInstanceOf(ForecastDay::class);
});

test('empty crop health is a successful contract response', function () {
    $data = fakeContractProvider()->fetchCropHealth(
        Farm::factory()->create(),
        since: now()->addYear(),
    );

    expect($data->isEmpty())->toBeTrue();
});

test('metric readings expose only platform metric and quality enums', function () {
    $data = fakeContractProvider()->fetchSoilHealth(Farm::factory()->create());

    expect($data->readings)->not->toBeEmpty();
    foreach ($data->readings as $reading) {
        expect($reading->metricType)->toBeInstanceOf(MetricType::class)
            ->and($reading->quality)->toBeInstanceOf(QualityFlag::class);
    }
});

test('every fake insight capability returns a typed domain DTO', function () {
    $farm = Farm::factory()->create();
    $cycle = CropCycle::factory()->for($farm)->create();
    $diagnosis = new DiagnosisRequest;
    $provider = fakeContractProvider();

    expect($provider->registerFarm($farm, $farm->boundary_hash))->toBeInstanceOf(ProviderFarmReference::class)
        ->and($provider->registerCropCycle($cycle))->toBeInstanceOf(ProviderFarmReference::class)
        ->and($provider->fetchSoilHealth($farm))->toBeInstanceOf(SoilHealthData::class)
        ->and($provider->fetchWeather($farm))->toBeInstanceOf(WeatherData::class)
        ->and($provider->fetchCropHealth($farm))->toBeInstanceOf(CropHealthData::class)
        ->and($provider->fetchWaterStress($farm))->toBeInstanceOf(WaterStressData::class)
        ->and($provider->fetchSoilMoisture($farm))->toBeInstanceOf(SoilMoistureData::class)
        ->and($provider->fetchIrrigationAdvisory($farm))->toBeInstanceOf(AdvisoryCollection::class)
        ->and($provider->fetchPestForewarning($farm))->toBeInstanceOf(AdvisoryCollection::class)
        ->and($provider->fetchCropPractices($cycle))->toBeInstanceOf(AdvisoryCollection::class)
        ->and($provider->submitDiagnosis($diagnosis, 'diagnosis-key'))->toBeInstanceOf(DiagnosisSubmission::class)
        ->and($provider->fetchDiagnosisResult($diagnosis))->toBeInstanceOf(DiagnosisResult::class)
        ->and($provider->healthCheck())->toBeInstanceOf(ProviderHealth::class);
});
