<?php

declare(strict_types=1);

use App\Actions\Sync\RecordSyncRun;
use App\Enums\FarmProviderLinkStatus;
use App\Enums\ProviderStatus;
use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Jobs\RegisterFarmWithProvider;
use App\Models\Farm;
use App\Models\FarmProviderLink;
use App\Models\IntegrationAccount;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\Integration\CircuitBreaker;
use Illuminate\Support\Facades\Queue;

test('creating a farm records and queues provider registration after commit', function () {
    Queue::fake();
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->postJson('/api/v1/farms', [
        'name' => 'Queued Plot',
        'boundary_geojson' => validFarmBoundary(),
    ])->assertCreated()
        ->assertJsonPath('data.provider_status', ProviderStatus::Pending->value);

    $farm = Farm::query()->sole();
    $run = SyncRun::query()->sole();

    expect($run->uuid)->toBe($response->json('meta.sync_run_id'))
        ->and($run->sync_type)->toBe(SyncType::FarmRegistration)
        ->and($run->status)->toBe(SyncStatus::Pending)
        ->and($run->idempotency_key)->toContain($farm->boundary_hash);

    Queue::assertPushedOn('sync', RegisterFarmWithProvider::class);
});

test('the fake provider registers a farm and persists one idempotent link', function () {
    $account = IntegrationAccount::factory()->fakeProvider()->create();
    $farm = Farm::factory()->create();
    $firstRun = SyncRun::factory()->for($farm)->create();

    runRegistrationJob($farm, $firstRun);

    expect($farm->refresh()->provider_status)->toBe(ProviderStatus::Registered)
        ->and($firstRun->refresh()->status)->toBe(SyncStatus::Succeeded)
        ->and($firstRun->records_written)->toBe(1);

    $link = FarmProviderLink::query()->sole();
    expect($link->integration_account_id)->toBe($account->getKey())
        ->and($link->boundary_hash)->toBe($farm->boundary_hash)
        ->and($link->status)->toBe(FarmProviderLinkStatus::Registered);

    $secondRun = SyncRun::factory()->for($farm)->create();
    runRegistrationJob($farm, $secondRun);

    expect(FarmProviderLink::query()->count())->toBe(1)
        ->and($secondRun->refresh()->status)->toBe(SyncStatus::Succeeded);
});

test('registration is skipped rather than failed while the circuit is open', function () {
    IntegrationAccount::factory()->fakeProvider()->create();
    $farm = Farm::factory()->create();
    $run = SyncRun::factory()->for($farm)->create();
    app(CircuitBreaker::class)->open('fake');

    runRegistrationJob($farm, $run);

    expect($run->refresh()->status)->toBe(SyncStatus::Skipped)
        ->and($run->error_code)->toBe('circuit_open')
        ->and(FarmProviderLink::query()->count())->toBe(0);
});

test('registration is skipped when its provider account is not configured', function () {
    $farm = Farm::factory()->create();
    $run = SyncRun::factory()->for($farm)->create();

    runRegistrationJob($farm, $run);

    expect($run->refresh()->status)->toBe(SyncStatus::Skipped)
        ->and($run->error_code)->toBe('integration_not_configured')
        ->and($farm->refresh()->provider_status)->toBe(ProviderStatus::Pending)
        ->and(FarmProviderLink::query()->count())->toBe(0);
});

test('changing a boundary marks its link stale and queues re-registration', function () {
    Queue::fake();
    $account = IntegrationAccount::factory()->fakeProvider()->create();
    $owner = User::factory()->create();
    $farm = Farm::factory()->for($owner, 'owner')->registered()->create();
    $link = FarmProviderLink::factory()->for($farm)->for($account)->create([
        'boundary_hash' => $farm->boundary_hash,
    ]);

    $this->actingAs($owner)->patchJson("/api/v1/farms/{$farm->uuid}", [
        'boundary_geojson' => validFarmBoundary(0.02),
    ])->assertOk()->assertJsonPath('data.provider_status', ProviderStatus::Pending->value);

    expect($link->refresh()->status)->toBe(FarmProviderLinkStatus::Stale)
        ->and(SyncRun::query()->where('sync_type', SyncType::FarmRegistration)->count())->toBe(1);
    Queue::assertPushedOn('sync', RegisterFarmWithProvider::class);
});

test('the hourly sweep requeues recent failed registrations', function () {
    Queue::fake();
    $farm = Farm::factory()->create(['provider_status' => ProviderStatus::Failed]);
    $run = SyncRun::factory()->for($farm)->create([
        'status' => SyncStatus::Failed,
        'idempotency_key' => 'farm-registration:'.$farm->getKey().':'.$farm->boundary_hash,
        'error_code' => 'provider_unavailable',
    ]);

    $this->artisan('farming:retry-failed-registrations')
        ->expectsOutput('1 farm registration retries queued.')
        ->assertSuccessful();

    expect($farm->refresh()->provider_status)->toBe(ProviderStatus::Pending)
        ->and($run->refresh()->status)->toBe(SyncStatus::Pending)
        ->and($run->trigger)->toBe(SyncTrigger::Retry);
    Queue::assertPushedOn('sync', RegisterFarmWithProvider::class);
});

function runRegistrationJob(Farm $farm, SyncRun $run): void
{
    (new RegisterFarmWithProvider(
        $farm->getKey(),
        $run->getKey(),
        $farm->boundary_hash,
    ))->handle(
        app(FarmingInsightsProvider::class),
        app(CircuitBreaker::class),
        app(RecordSyncRun::class),
    );
}
