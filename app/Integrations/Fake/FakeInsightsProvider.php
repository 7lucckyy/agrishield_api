<?php

declare(strict_types=1);

namespace App\Integrations\Fake;

use App\DTOs\Provider\AdvisoryCollection;
use App\DTOs\Provider\AdvisoryItem;
use App\DTOs\Provider\CropHealthData;
use App\DTOs\Provider\DiagnosisResult;
use App\DTOs\Provider\DiagnosisSubmission;
use App\DTOs\Provider\ProviderFarmReference;
use App\DTOs\Provider\ProviderHealth;
use App\DTOs\Provider\SoilHealthData;
use App\DTOs\Provider\SoilMoistureData;
use App\DTOs\Provider\WaterStressData;
use App\DTOs\Provider\WeatherData;
use App\Enums\AdvisorySeverity;
use App\Enums\AdvisoryType;
use App\Enums\DiagnosisResultStatus;
use App\Enums\MetricType;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Exceptions\Provider\ProviderUnavailable;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\CropCycle;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final readonly class FakeInsightsProvider implements FarmingInsightsProvider
{
    public function __construct(private FakeDataGenerator $generator) {}

    public function name(): string
    {
        return 'fake';
    }

    public function registerFarm(Farm $farm, string $idempotencyKey): ProviderFarmReference
    {
        $this->simulateLatencyAndFailure('register-farm|'.$farm->uuid);

        return new ProviderFarmReference(
            providerFarmId: 'fake-farm-'.Str::lower(Str::substr(hash('sha256', $idempotencyKey), 0, 24)),
            registeredAt: CarbonImmutable::now(),
            metadata: ['simulated' => true],
        );
    }

    public function registerCropCycle(CropCycle $cycle): ProviderFarmReference
    {
        $this->simulateLatencyAndFailure('register-cycle|'.$cycle->getKey());

        return new ProviderFarmReference(
            providerFarmId: 'fake-cycle-'.$cycle->getKey(),
            registeredAt: CarbonImmutable::now(),
            metadata: ['simulated' => true],
        );
    }

    public function fetchSoilHealth(Farm $farm, ?CarbonInterface $since = null): SoilHealthData
    {
        $this->simulateLatencyAndFailure('soil|'.$farm->uuid);
        $capturedAt = CarbonImmutable::now()->startOfDay();

        if ($since !== null && $since->gte($capturedAt)) {
            return new SoilHealthData($farm->uuid, [], null, $this->name());
        }

        return new SoilHealthData(
            farmReference: $farm->uuid,
            readings: $this->generator->soilHealth($this->seed($farm, 'soil'), $capturedAt),
            capturedAt: $capturedAt,
            source: $this->name(),
        );
    }

    public function fetchWeather(Farm $farm): WeatherData
    {
        $this->simulateLatencyAndFailure('weather|'.$farm->uuid);

        return new WeatherData(
            farmReference: $farm->uuid,
            days: $this->generator->weatherForecast($this->seed($farm, 'weather'), CarbonImmutable::today()),
            fetchedAt: CarbonImmutable::now(),
            source: $this->name(),
        );
    }

    public function fetchCropHealth(Farm $farm, ?CarbonInterface $since = null): CropHealthData
    {
        $this->simulateLatencyAndFailure('ndvi|'.$farm->uuid);
        $readings = $this->generator->seasonalIndexSeries(
            seed: $this->seed($farm, 'ndvi'),
            metric: MetricType::Ndvi,
            since: $since ?? CarbonImmutable::now()->subDays(30),
            cadenceDays: 5,
            resolutionMeters: 10,
            cloudProbability: 0.25,
        );

        return new CropHealthData($farm->uuid, $readings, $readings === [] ? null : end($readings)->capturedAt, $this->name());
    }

    public function fetchWaterStress(Farm $farm, ?CarbonInterface $since = null): WaterStressData
    {
        $this->simulateLatencyAndFailure('lswi|'.$farm->uuid);
        $readings = $this->generator->seasonalIndexSeries(
            seed: $this->seed($farm, 'lswi'),
            metric: MetricType::Lswi,
            since: $since ?? CarbonImmutable::now()->subDays(30),
            cadenceDays: 5,
            resolutionMeters: 10,
            cloudProbability: 0.25,
        );

        return new WaterStressData($farm->uuid, $readings, $readings === [] ? null : end($readings)->capturedAt, $this->name());
    }

    public function fetchSoilMoisture(Farm $farm, ?CarbonInterface $since = null): SoilMoistureData
    {
        $this->simulateLatencyAndFailure('moisture|'.$farm->uuid);
        $readings = $this->generator->seasonalIndexSeries(
            seed: $this->seed($farm, 'moisture'),
            metric: MetricType::SoilMoisture,
            since: $since ?? CarbonImmutable::now()->subDays(30),
            cadenceDays: 12,
            resolutionMeters: 10,
            cloudProbability: 0.1,
        );

        return new SoilMoistureData($farm->uuid, $readings, $readings === [] ? null : end($readings)->capturedAt, $this->name());
    }

    public function fetchIrrigationAdvisory(Farm $farm): AdvisoryCollection
    {
        $this->simulateLatencyAndFailure('irrigation|'.$farm->uuid);

        return new AdvisoryCollection($farm->uuid, [new AdvisoryItem(
            type: AdvisoryType::Irrigation,
            title: 'Review soil moisture before irrigating',
            summary: 'Irrigate early in the morning if the upper soil layer is dry.',
            severity: AdvisorySeverity::Info,
            payload: ['simulated' => true],
            observedAt: CarbonImmutable::now(),
            validFrom: CarbonImmutable::today(),
            validUntil: CarbonImmutable::tomorrow(),
        )], $this->name());
    }

    public function fetchPestForewarning(Farm $farm): AdvisoryCollection
    {
        $this->simulateLatencyAndFailure('pest|'.$farm->uuid);

        return new AdvisoryCollection($farm->uuid, [new AdvisoryItem(
            type: AdvisoryType::PestWarning,
            title: 'Scout the crop for early pest activity',
            summary: 'Inspect representative plants and record any visible damage.',
            severity: AdvisorySeverity::Low,
            payload: ['simulated' => true],
            observedAt: CarbonImmutable::now(),
            validFrom: CarbonImmutable::today(),
            validUntil: CarbonImmutable::now()->addWeek(),
        )], $this->name());
    }

    public function fetchCropPractices(CropCycle $cycle): AdvisoryCollection
    {
        $this->simulateLatencyAndFailure('practices|'.$cycle->getKey());
        $farm = $cycle->farm;

        return new AdvisoryCollection($farm->uuid, [new AdvisoryItem(
            type: AdvisoryType::CropPractice,
            title: 'Follow the recommended crop calendar',
            summary: 'Time field operations around the recorded planting date and local conditions.',
            severity: AdvisorySeverity::Info,
            payload: ['crop_cycle_id' => $cycle->getKey(), 'simulated' => true],
            observedAt: CarbonImmutable::now(),
            validFrom: $cycle->planting_date,
            validUntil: $cycle->expected_harvest_date,
        )], $this->name());
    }

    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission
    {
        $this->simulateLatencyAndFailure('diagnosis-submit|'.$idempotencyKey);

        return new DiagnosisSubmission(
            providerCaseId: 'fake-case-'.Str::substr(hash('sha256', $idempotencyKey), 0, 24),
            submittedAt: CarbonImmutable::now(),
            expectedBy: CarbonImmutable::now()->addMinutes(5),
        );
    }

    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult
    {
        $this->simulateLatencyAndFailure('diagnosis-result|'.$request->getKey());

        return new DiagnosisResult(
            status: DiagnosisResultStatus::Completed,
            diagnosis: 'Simulated leaf stress',
            recommendation: 'Inspect the crop and consult a local agronomist before treatment.',
            confidence: 0.82,
            detectedLabels: [['label' => 'leaf_stress', 'confidence' => 0.82]],
            completedAt: CarbonImmutable::now(),
        );
    }

    public function healthCheck(): ProviderHealth
    {
        $startedAt = hrtime(true);
        $latencyMs = $this->latencyMilliseconds();
        if ($latencyMs > 0) {
            usleep($latencyMs * 1000);
        }

        return new ProviderHealth(
            reachable: $this->failureRate() < 1.0,
            latencyMs: (int) ((hrtime(true) - $startedAt) / 1_000_000),
            checkedAt: CarbonImmutable::now(),
            note: 'Deterministic fake provider',
        );
    }

    private function seed(Farm $farm, string $scope): int
    {
        return abs(crc32($this->configuredSeed().'|'.$farm->uuid.'|'.$scope));
    }

    private function simulateLatencyAndFailure(string $scope): void
    {
        $latencyMs = $this->latencyMilliseconds();
        if ($latencyMs > 0) {
            usleep($latencyMs * 1000);
        }

        if ((bool) config('farming.providers.fake.chaos', false) && random_int(1, 100) === 1) {
            throw new ProviderContractViolation(
                'The fake provider generated a contract violation.',
                'provider_contract_violation',
            );
        }

        if ($this->failureRate() > 0
            && (random_int(0, 10_000) / 10_000) < $this->failureRate()) {
            throw new ProviderUnavailable(
                'The fake provider is simulating an outage for '.$scope.'.',
                'provider_unavailable',
                retryable: true,
            );
        }
    }

    private function configuredSeed(): int
    {
        return (int) config('farming.providers.fake.seed', 1337);
    }

    private function latencyMilliseconds(): int
    {
        return max(0, (int) config('farming.providers.fake.latency_ms', 0));
    }

    private function failureRate(): float
    {
        return max(0.0, min(1.0, (float) config('farming.providers.fake.failure_rate', 0.0)));
    }
}
