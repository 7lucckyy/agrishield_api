<?php

declare(strict_types=1);

use App\Enums\CircuitState;
use App\Enums\FarmProviderLinkStatus;
use App\Enums\IntegrationStatus;
use App\Enums\SyncStatus;
use App\Models\Farm;
use App\Models\FarmProviderLink;
use App\Models\IntegrationAccount;
use App\Models\SyncRun;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('phase three integration tables expose the documented columns', function () {
    expect(Schema::hasColumns('integration_accounts', [
        'provider', 'status', 'credentials_ref', 'config', 'consecutive_failures',
        'circuit_state', 'circuit_opened_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('farm_provider_links', [
            'farm_id', 'integration_account_id', 'provider', 'provider_farm_id',
            'status', 'boundary_hash', 'provider_metadata', 'registered_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('sync_runs', [
            'uuid', 'farm_id', 'syncable_type', 'syncable_id', 'sync_type', 'status',
            'provider', 'trigger', 'attempts', 'records_written', 'idempotency_key',
        ]))->toBeTrue();
});

test('integration state columns are cast to platform enums and secrets stay hidden', function () {
    $account = IntegrationAccount::factory()->fakeProvider()->create([
        'credentials_ref' => 'FAKE_SECRET_NAME',
        'config' => ['base_url' => 'https://provider.invalid'],
    ]);
    $link = FarmProviderLink::factory()->for($account)->create();
    $run = SyncRun::factory()->create();

    expect($account->status)->toBe(IntegrationStatus::Active)
        ->and($account->circuit_state)->toBe(CircuitState::Closed)
        ->and($account->toArray())->not->toHaveKeys(['credentials_ref', 'config'])
        ->and($link->status)->toBe(FarmProviderLinkStatus::Registered)
        ->and($run->status)->toBe(SyncStatus::Pending);
});

test('a farm can have only one link per integration account', function () {
    $farm = Farm::factory()->create();
    $account = IntegrationAccount::factory()->fakeProvider()->create();
    FarmProviderLink::factory()->for($farm)->for($account)->create();

    expect(fn () => FarmProviderLink::factory()->for($farm)->for($account)->create())
        ->toThrow(QueryException::class);
});

test('sync idempotency keys are unique when present', function () {
    SyncRun::factory()->create(['idempotency_key' => 'stable-registration-key']);

    expect(fn () => SyncRun::factory()->create(['idempotency_key' => 'stable-registration-key']))
        ->toThrow(QueryException::class);
});
