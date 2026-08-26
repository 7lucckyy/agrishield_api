<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Sync\RecordSyncRun;
use App\Actions\Sync\SyncFarmInsights as PersistFarmInsights;
use App\Enums\SyncType;
use App\Exceptions\Provider\ProviderException;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\Farm;
use App\Models\SyncRun;
use App\Services\Integration\CircuitBreaker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class SyncFarmInsights implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 900;

    public function __construct(
        public readonly int $farmId,
        public readonly int $syncRunId,
        public readonly SyncType $type,
    ) {
        $this->onQueue('sync');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return $this->farmId.':'.$this->type->value;
    }

    public function handle(
        FarmingInsightsProvider $provider,
        CircuitBreaker $circuitBreaker,
        RecordSyncRun $recordSyncRun,
        PersistFarmInsights $syncFarmInsights,
    ): void {
        $farm = Farm::query()->findOrFail($this->farmId);
        $syncRun = SyncRun::query()->findOrFail($this->syncRunId);

        if (! $circuitBreaker->allowsRequest($provider->name())) {
            $recordSyncRun->skip($syncRun, 'circuit_open');

            return;
        }

        $recordSyncRun->startAttempt($syncRun);

        try {
            $recordsWritten = $syncFarmInsights->execute($farm, $this->type, $provider);
            $recordSyncRun->succeed($syncRun, $recordsWritten);
            $circuitBreaker->recordSuccess($provider->name());
            $farm->forceFill(['last_synced_at' => now()])->save();
        } catch (ProviderException $exception) {
            $recordSyncRun->fail($syncRun, $exception->errorCode, $exception->getMessage(), $exception->providerRequestId);
            $circuitBreaker->recordFailure($provider->name());

            if ($exception->retryable) {
                throw $exception;
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        SyncRun::query()->whereKey($this->syncRunId)->whereNull('error_code')->update([
            'error_code' => 'insight_sync_failed',
            'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            'completed_at' => now(),
        ]);
    }
}
