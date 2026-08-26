<?php

declare(strict_types=1);

use App\Actions\Sync\SyncFarmInsights;
use App\Enums\OrganizationRole;
use App\Enums\SyncType;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Jobs\SyncFarmInsights as SyncFarmInsightsJob;
use App\Models\Advisory;
use App\Models\AuditLog;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\SatelliteObservation;
use App\Models\SyncRun;
use App\Models\User;
use App\Models\WeatherForecast;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Carbon::setTestNow('2026-08-24 09:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('all farming insight products sync idempotently through the fake provider', function () {
    $farm = Farm::factory()->registered()->create();
    CropCycle::factory()->for($farm)->active()->create();
    $sync = app(SyncFarmInsights::class);
    $provider = app(FarmingInsightsProvider::class);
    $types = [
        SyncType::SoilHealth,
        SyncType::Weather,
        SyncType::CropHealth,
        SyncType::WaterStress,
        SyncType::SoilMoisture,
        SyncType::IrrigationAdvisory,
        SyncType::PestForewarning,
        SyncType::CropPractices,
    ];

    foreach ($types as $type) {
        expect($sync->execute($farm, $type, $provider))->toBeGreaterThan(0);
    }

    $counts = [SatelliteObservation::query()->count(), WeatherForecast::query()->count(), Advisory::query()->count()];

    foreach ($types as $type) {
        $sync->execute($farm->refresh(), $type, $provider);
    }

    expect([SatelliteObservation::query()->count(), WeatherForecast::query()->count(), Advisory::query()->count()])->toBe($counts)
        ->and(WeatherForecast::query()->count())->toBe(15)
        ->and(Advisory::query()->count())->toBe(3);
});

test('insight endpoints return pending first sync instead of not found', function (string $path, string $statePath) {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();

    $this->actingAs($owner)
        ->getJson("/api/v1/farms/{$farm->uuid}/{$path}")
        ->assertSuccessful()
        ->assertJsonPath($statePath, 'pending_first_sync');
})->with([
    'soil health' => ['soil-health', 'meta.state'],
    'weather' => ['weather', 'meta.state'],
    'observations' => ['satellite-observations', 'meta.state'],
]);

test('synced insight products are readable with freshness metadata', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();
    $sync = app(SyncFarmInsights::class);
    $provider = app(FarmingInsightsProvider::class);
    $sync->execute($farm, SyncType::SoilHealth, $provider);
    $sync->execute($farm, SyncType::Weather, $provider);
    $sync->execute($farm, SyncType::CropHealth, $provider);

    $this->actingAs($owner)->getJson("/api/v1/farms/{$farm->uuid}/soil-health")
        ->assertSuccessful()->assertJsonCount(5, 'data.metrics')->assertJsonPath('meta.state', 'ready');
    $this->actingAs($owner)->getJson("/api/v1/farms/{$farm->uuid}/weather")
        ->assertSuccessful()->assertJsonCount(15, 'data.days')->assertJsonPath('meta.freshness.cadence_days', 1);
    $this->actingAs($owner)->getJson("/api/v1/farms/{$farm->uuid}/satellite-observations?filter[metric_type]=ndvi")
        ->assertSuccessful()->assertJsonPath('meta.state', 'ready')->assertJsonStructure(['data', 'meta' => ['freshness']]);
});

test('farm owners can queue selected insight syncs and inspect their runs', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/sync", [
        'types' => ['weather', 'crop_health'],
    ])->assertAccepted()->assertJsonCount(2, 'data.runs');

    expect(SyncRun::query()->count())->toBe(2);
    Queue::assertPushed(SyncFarmInsightsJob::class, 2);
    $this->actingAs($owner)->getJson("/api/v1/farms/{$farm->uuid}/sync-runs")
        ->assertSuccessful()->assertJsonCount(2, 'data');
});

test('duplicate in-flight syncs are rejected and scheduled sweeps select only due farms', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $dueFarm = Farm::factory()->for($owner, 'owner')->registered()->create();
    $currentFarm = Farm::factory()->registered()->create();
    WeatherForecast::factory()->for($currentFarm)->create(['forecast_date' => today(), 'fetched_at' => now()]);

    $this->actingAs($owner);
    $this->postJson("/api/v1/farms/{$dueFarm->uuid}/sync", ['types' => ['weather']])->assertAccepted();
    $this->postJson("/api/v1/farms/{$dueFarm->uuid}/sync", ['types' => ['weather']])
        ->assertConflict()->assertJsonPath('error_code', 'sync_already_running');

    $this->artisan('farming:sync', ['type' => 'weather'])
        ->expectsOutput('0 weather syncs queued.')
        ->assertSuccessful();
    expect(SyncRun::query()->whereBelongsTo($currentFarm)->count())->toBe(0);
});

test('a farm viewer can acknowledge an advisory', function () {
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();
    app(SyncFarmInsights::class)->execute($farm, SyncType::PestForewarning, app(FarmingInsightsProvider::class));
    $advisory = Advisory::query()->sole();

    $this->actingAs($owner)->postJson("/api/v1/farms/{$farm->uuid}/advisories/{$advisory->getKey()}/acknowledge", [
        'read' => true,
        'acted' => false,
        'feedback' => 'Scouted, no larvae found',
    ])->assertSuccessful()
        ->assertJsonPath('data.acknowledgement.feedback', 'Scouted, no larvae found');
});

test('organization agronomists can issue manual advisories', function () {
    $owner = User::factory()->create();
    $agronomist = User::factory()->create();
    $organization = Organization::factory()->create();
    attachOrganizationRole($agronomist, $organization, OrganizationRole::Agronomist);
    $farm = Farm::factory()->for($owner, 'owner')->for($organization)->registered()->create();

    $this->actingAs($agronomist)->postJson("/api/v1/farms/{$farm->uuid}/advisories", [
        'type' => 'crop_health',
        'severity' => 'medium',
        'title' => 'Inspect the northern plot',
        'summary' => 'A field inspection is recommended within two days.',
    ])->assertCreated()
        ->assertJsonPath('data.source', 'manual')
        ->assertJsonPath('data.title', 'Inspect the northern plot');

    expect(Advisory::query()->sole()->issued_by_user_id)->toBe($agronomist->getKey())
        ->and(AuditLog::query()->sole()->action)->toBe('advisory.created');
});

test('outsiders cannot discover farm insights', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();

    $this->actingAs($stranger)->getJson("/api/v1/farms/{$farm->uuid}/weather")
        ->assertNotFound()->assertJsonPath('error_code', 'not_found');
});
