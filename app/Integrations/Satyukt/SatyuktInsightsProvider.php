<?php

declare(strict_types=1);

namespace App\Integrations\Satyukt;

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
use App\Models\CropCycle;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Carbon\CarbonInterface;

/**
 * Satyukt adapter.
 *
 * STATUS: STUB. No request fields or response fields are assumed here.
 *
 * Blocked on these provider-contract questions:
 *
 * 1. Sandbox and production base URLs.
 * 2. Authentication scheme and credential rotation policy.
 * 3. Rate limits, quotas, and burst behaviour.
 * 4. Farm registration request and response shape.
 * 5. Boundary format, coordinate order, and CRS.
 * 6. Maximum boundary complexity and area.
 * 7. Registration idempotency and idempotency-key support.
 * 8. Boundary change semantics.
 * 9. Response shape for every insight product.
 * 10. Units and scaling for every metric.
 * 11. Satellite capture time versus provider response time.
 * 12. Quality and cloud flags per observation.
 * 13. Historical endpoint availability and retention.
 * 14. Raster URL access policy and expiry.
 * 15. Diagnosis upload format and limits.
 * 16. Diagnosis polling or webhook workflow.
 * 17. Diagnosis result labels, confidence, recommendations, and language.
 * 18. Diagnosis turnaround distribution.
 * 19. Error codes and retry semantics.
 * 20. Status page and maintenance notifications.
 * 21. Crop taxonomy mapping.
 * 22. Personal data that must be scrubbed before storage.
 */
final class SatyuktInsightsProvider implements FarmingInsightsProvider
{
    public function name(): string
    {
        return 'satyukt';
    }

    public function registerFarm(Farm $farm, string $idempotencyKey): ProviderFarmReference
    {
        $this->notImplemented('farm registration');
    }

    public function registerCropCycle(CropCycle $cycle): ProviderFarmReference
    {
        $this->notImplemented('crop-cycle registration');
    }

    public function fetchSoilHealth(Farm $farm, ?CarbonInterface $since = null): SoilHealthData
    {
        $this->notImplemented('soil health');
    }

    public function fetchWeather(Farm $farm): WeatherData
    {
        $this->notImplemented('weather');
    }

    public function fetchCropHealth(Farm $farm, ?CarbonInterface $since = null): CropHealthData
    {
        $this->notImplemented('crop health');
    }

    public function fetchWaterStress(Farm $farm, ?CarbonInterface $since = null): WaterStressData
    {
        $this->notImplemented('water stress');
    }

    public function fetchSoilMoisture(Farm $farm, ?CarbonInterface $since = null): SoilMoistureData
    {
        $this->notImplemented('soil moisture');
    }

    public function fetchIrrigationAdvisory(Farm $farm): AdvisoryCollection
    {
        $this->notImplemented('irrigation advisories');
    }

    public function fetchPestForewarning(Farm $farm): AdvisoryCollection
    {
        $this->notImplemented('pest forewarning');
    }

    public function fetchCropPractices(CropCycle $cycle): AdvisoryCollection
    {
        $this->notImplemented('crop practices');
    }

    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission
    {
        $this->notImplemented('diagnosis submission');
    }

    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult
    {
        $this->notImplemented('diagnosis results');
    }

    public function healthCheck(): ProviderHealth
    {
        $this->notImplemented('health checks');
    }

    private function notImplemented(string $capability): never
    {
        throw new ProviderNotImplemented(
            'The Satyukt '.$capability.' contract has not been supplied.',
            'provider_not_implemented',
        );
    }
}
