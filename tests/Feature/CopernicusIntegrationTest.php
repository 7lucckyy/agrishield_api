<?php

declare(strict_types=1);

use App\Actions\Sync\SyncCopernicusSections;
use App\Enums\MetricType;
use App\Enums\ProviderStatus;
use App\Enums\QualityFlag;
use App\Exceptions\Provider\ProviderAuthFailed;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Integrations\Copernicus\CopernicusInsightsProvider;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Models\SatelliteObservation;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('farming.provider', 'copernicus');
    config()->set('farming.providers.copernicus.client_id', 'test-client');
    config()->set('farming.providers.copernicus.client_secret', 'test-secret');
    Cache::forget('copernicus:oauth:'.hash('sha256', 'test-client|test-secret'));
});

test('copernicus statistics map a cloud-masked daily NDVI reading', function () {
    $calls = [];
    Http::fake(function (Request $request) use (&$calls) {
        $calls[] = $request;
        if (str_contains($request->url(), '/token')) {
            return Http::response(['access_token' => 'test-token', 'expires_in' => 300]);
        }

        return Http::response(['data' => [
            ['interval' => ['from' => now()->subDays(2)->startOfDay()->toIso8601String()], 'outputs' => ['data' => ['bands' => ['B0' => ['stats' => ['mean' => 0.62, 'sampleCount' => 100, 'noDataCount' => 20]]]]]],
        ]]);
    });

    $farm = Farm::factory()->create();
    $provider = app(FarmingInsightsProvider::class);
    expect($provider)->toBeInstanceOf(CopernicusInsightsProvider::class);
    $reading = $provider->fetchCropHealth($farm)->readings[0];
    expect($reading->metricType)->toBe(MetricType::Ndvi)
        ->and($reading->value)->toBe(0.62)
        ->and($reading->quality)->toBe(QualityFlag::Good)
        ->and($reading->statistics['coverage'])->toBe(0.8)
        ->and($calls)->toHaveCount(2)
        ->and($calls[1]->hasHeader('Authorization', 'Bearer test-token'))->toBeTrue()
        ->and($calls[1]['input']['data'][0]['type'])->toBe('sentinel-2-l2a')
        ->and($calls[1]['input']['bounds']['geometry'])->toBe($farm->boundary_geojson);
});

test('copernicus skips fully clouded intervals rather than fabricating NDVI', function () {
    Http::fake([
        '*token' => Http::response(['access_token' => 'test-token']),
        '*statistics*' => Http::response(['data' => [
            ['interval' => ['from' => now()->subDay()->toIso8601String()], 'outputs' => ['data' => ['bands' => ['B0' => ['stats' => ['mean' => 0, 'sampleCount' => 100, 'noDataCount' => 100]]]]]],
        ]]),
    ]);

    expect(app(FarmingInsightsProvider::class)->fetchCropHealth(Farm::factory()->create())->readings)->toBe([]);
});

test('copernicus refuses registration when OAuth credentials are absent', function () {
    config()->set('farming.providers.copernicus.client_secret', null);

    expect(fn () => app(FarmingInsightsProvider::class)->registerFarm(Farm::factory()->create(), 'boundary'))
        ->toThrow(ProviderAuthFailed::class);
});

test('copernicus section sync is idempotent and powers farmer productivity zones', function () {
    $values = [0.25, 0.5, 0.78];
    $statisticCalls = 0;
    Http::fake(function (Request $request) use (&$statisticCalls, $values) {
        if (str_contains($request->url(), '/token')) {
            return Http::response(['access_token' => 'test-token']);
        }

        $value = $values[$statisticCalls % 3];
        $statisticCalls++;

        return Http::response(['data' => [
            ['interval' => ['from' => now()->subDay()->startOfDay()->toIso8601String()], 'outputs' => ['data' => ['bands' => ['B0' => ['stats' => ['mean' => $value, 'sampleCount' => 100, 'noDataCount' => 0]]]]]],
        ]]);
    });

    $owner = User::factory()->create();
    $farm = Farm::factory()->create(['owner_user_id' => $owner->id]);
    $crop = Crop::factory()->create();
    foreach (range(1, 3) as $index) {
        FarmSection::factory()->create(['farm_id' => $farm->id, 'crop_id' => $crop->id, 'boundary_geojson' => validFarmBoundary($index)]);
    }

    $sync = app(SyncCopernicusSections::class);
    expect($sync->execute($farm))->toBe(3)
        ->and($sync->execute($farm))->toBe(0)
        ->and(SatelliteObservation::query()->where('source', 'copernicus')->count())->toBe(3);

    $this->actingAs($owner)->getJson(route('farms.productivity-zones.show', $farm))
        ->assertOk()->assertJsonPath('data.crop_groups.'.$crop->id.'.state', 'ready');
});

test('manual copernicus sync accepts only weather and crop health', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->create(['owner_user_id' => $owner->id, 'provider_status' => ProviderStatus::Registered]);
    $url = route('farms.sync.store', $farm);

    $this->actingAs($owner)->postJson($url, ['types' => ['soil_health']])
        ->assertUnprocessable()->assertJsonValidationErrors(['types.0']);
});
