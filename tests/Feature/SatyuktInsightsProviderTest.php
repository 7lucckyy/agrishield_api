<?php

declare(strict_types=1);

use App\Exceptions\Provider\ProviderNotImplemented;
use App\Integrations\Satyukt\SatyuktInsightsProvider;
use App\Models\CropCycle;
use App\Models\DiagnosisRequest;
use App\Models\Farm;

test('every Satyukt capability remains explicitly blocked until its contract is supplied', function () {
    $provider = new SatyuktInsightsProvider;
    $farm = Farm::factory()->create();
    $cycle = CropCycle::factory()->for($farm)->create();
    $diagnosis = new DiagnosisRequest;

    $calls = [
        fn () => $provider->registerFarm($farm, $farm->boundary_hash),
        fn () => $provider->registerCropCycle($cycle),
        fn () => $provider->fetchSoilHealth($farm),
        fn () => $provider->fetchWeather($farm),
        fn () => $provider->fetchCropHealth($farm),
        fn () => $provider->fetchWaterStress($farm),
        fn () => $provider->fetchSoilMoisture($farm),
        fn () => $provider->fetchIrrigationAdvisory($farm),
        fn () => $provider->fetchPestForewarning($farm),
        fn () => $provider->fetchCropPractices($cycle),
        fn () => $provider->submitDiagnosis($diagnosis, 'diagnosis-key'),
        fn () => $provider->fetchDiagnosisResult($diagnosis),
        fn () => $provider->healthCheck(),
    ];

    foreach ($calls as $call) {
        try {
            $call();
            $this->fail('The Satyukt stub unexpectedly executed a provider capability.');
        } catch (ProviderNotImplemented $exception) {
            expect($exception->errorCode)->toBe('provider_not_implemented')
                ->and($exception->retryable)->toBeFalse();
        }
    }
});

test('the Satyukt stub preserves all twenty two provider contract questions', function () {
    $reflection = new ReflectionClass(SatyuktInsightsProvider::class);
    $documentation = $reflection->getDocComment();

    expect($documentation)->toBeString();
    if (is_string($documentation)) {
        preg_match_all('/^ \* \d+\./m', $documentation, $matches);
        expect($matches[0])->toHaveCount(22);
    }
});
