<?php

declare(strict_types=1);

namespace App\Integrations\Copernicus;

use App\DTOs\Provider\MetricReading;
use App\Enums\MetricType;
use App\Enums\QualityFlag;
use App\Exceptions\Provider\ProviderAuthFailed;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Integrations\Support\ProviderExceptionMapper;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class CopernicusNdviClient
{
    public function __construct(private readonly ProviderExceptionMapper $exceptions) {}

    /** @param array<string, mixed> $boundary */
    public function latest(array $boundary, string $reference): ?MetricReading
    {
        if (! in_array($boundary['type'] ?? null, ['Polygon', 'MultiPolygon'], true) || ! is_array($boundary['coordinates'] ?? null)) {
            throw new ProviderContractViolation('A mapped farm boundary is required for satellite analysis.', 'copernicus_invalid_boundary');
        }

        $end = CarbonImmutable::now('UTC')->addDay()->startOfDay();
        $start = $end->subDays(12);
        $payload = [
            'input' => [
                'bounds' => ['geometry' => $boundary, 'properties' => ['crs' => 'http://www.opengis.net/def/crs/OGC/1.3/CRS84']],
                'data' => [['type' => 'sentinel-2-l2a', 'dataFilter' => ['mosaickingOrder' => 'leastCC']]],
            ],
            'aggregation' => [
                'timeRange' => ['from' => $start->toIso8601String(), 'to' => $end->toIso8601String()],
                'aggregationInterval' => ['of' => 'P1D'],
                'evalscript' => <<<'EVALSCRIPT'
//VERSION=3
function setup() {
  return { input: [{ bands: ["B04", "B08", "SCL", "dataMask"] }], output: [{ id: "data", bands: 1, sampleType: "FLOAT32" }, { id: "dataMask", bands: 1 }] };
}
function evaluatePixel(sample) {
  const valid = sample.dataMask === 1 && (sample.SCL === 4 || sample.SCL === 5) && (sample.B08 + sample.B04) > 0;
  return { data: [valid ? (sample.B08 - sample.B04) / (sample.B08 + sample.B04) : 0], dataMask: [valid ? 1 : 0] };
}
EVALSCRIPT,
                'resx' => 10,
                'resy' => 10,
            ],
        ];

        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->connectTimeout((int) config('farming.providers.copernicus.connect_timeout'))
                ->timeout((int) config('farming.providers.copernicus.read_timeout'))
                ->post((string) config('farming.providers.copernicus.statistics_url'), $payload);
        } catch (ConnectionException $exception) {
            throw $this->exceptions->fromThrowable($exception);
        }

        if ($response->failed()) {
            throw $this->exceptions->fromResponse($response, $response->header('X-Request-Id'));
        }

        $intervals = $response->json('data');
        if (! is_array($intervals)) {
            throw new ProviderContractViolation('Copernicus returned an invalid statistics response.', 'copernicus_invalid_response');
        }

        foreach (array_reverse($intervals) as $interval) {
            if (! is_array($interval)) {
                continue;
            }

            $stats = data_get($interval, 'outputs.data.bands.B0.stats');
            $mean = is_array($stats) ? ($stats['mean'] ?? null) : null;
            $samples = is_array($stats) ? ($stats['sampleCount'] ?? null) : null;
            $noData = is_array($stats) ? ($stats['noDataCount'] ?? null) : null;
            $from = data_get($interval, 'interval.from');
            if (! is_numeric($mean) || ! is_numeric($samples) || ! is_numeric($noData) || ! is_string($from)) {
                continue;
            }

            $validSamples = (int) $samples - (int) $noData;
            if ($validSamples <= 0 || (float) $mean < -1 || (float) $mean > 1) {
                continue;
            }

            $coverage = $validSamples / max(1, (int) $samples);
            $capturedAt = CarbonImmutable::parse($from)->utc();

            return new MetricReading(
                MetricType::Ndvi,
                (float) $mean,
                'index',
                $capturedAt,
                10,
                ['sample_count' => (int) $samples, 'valid_sample_count' => $validSamples, 'coverage' => round($coverage, 4)],
                null,
                $coverage >= 0.5 ? QualityFlag::Good : QualityFlag::PartialCloud,
                hash('sha256', $reference.'|'.$capturedAt->toDateString()),
            );
        }

        return null;
    }

    public function accessToken(): string
    {
        $clientId = (string) config('farming.providers.copernicus.client_id');
        $clientSecret = (string) config('farming.providers.copernicus.client_secret');
        if ($clientId === '' || $clientSecret === '') {
            throw new ProviderAuthFailed('Copernicus OAuth credentials are not configured.', 'copernicus_not_configured');
        }

        return Cache::remember('copernicus:oauth:'.hash('sha256', $clientId.'|'.$clientSecret), now()->addMinutes(4), function () use ($clientId, $clientSecret): string {
            try {
                $response = Http::asForm()->acceptJson()
                    ->connectTimeout((int) config('farming.providers.copernicus.connect_timeout'))
                    ->timeout((int) config('farming.providers.copernicus.read_timeout'))
                    ->post((string) config('farming.providers.copernicus.token_url'), [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ]);
            } catch (ConnectionException $exception) {
                throw $this->exceptions->fromThrowable($exception);
            }

            if ($response->failed()) {
                throw $this->exceptions->fromResponse($response);
            }

            $token = $response->json('access_token');
            if (! is_string($token) || $token === '') {
                throw new ProviderContractViolation('Copernicus returned no OAuth access token.', 'copernicus_invalid_token');
            }

            return $token;
        });
    }
}
