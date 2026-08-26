<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Exceptions\SyncAlreadyRunningException;
use App\Jobs\SyncFarmInsights;
use App\Models\Farm;
use App\Models\SyncRun;
use App\Models\User;

final readonly class QueueInsightSync
{
    public function __construct(private RecordSyncRun $recordSyncRun) {}

    public function execute(
        Farm $farm,
        SyncType $type,
        SyncTrigger $trigger,
        ?User $triggeredBy = null,
        ?string $idempotencyKey = null,
    ): SyncRun {
        if ($idempotencyKey !== null) {
            $existing = $farm->syncRuns()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $inFlight = $farm->syncRuns()
            ->where('sync_type', $type)
            ->whereIn('status', [SyncStatus::Pending, SyncStatus::Running])
            ->first();

        if ($inFlight !== null) {
            if ($idempotencyKey !== null && $inFlight->idempotency_key === $idempotencyKey) {
                return $inFlight;
            }

            throw new SyncAlreadyRunningException;
        }

        $syncRun = $this->recordSyncRun->create(
            $farm,
            $type,
            (string) config('farming.provider'),
            $trigger,
            $idempotencyKey,
            $triggeredBy,
        );
        SyncFarmInsights::dispatch($farm->getKey(), $syncRun->getKey(), $type)->afterCommit();

        return $syncRun;
    }
}
