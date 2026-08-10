<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Jobs\RegisterFarmWithProvider;
use App\Models\Farm;
use App\Models\SyncRun;
use App\Models\User;

final readonly class QueueFarmRegistration
{
    public function __construct(private RecordSyncRun $recordSyncRun) {}

    public function execute(Farm $farm, SyncTrigger $trigger, ?User $triggeredBy = null): SyncRun
    {
        $provider = (string) config('farming.provider');
        $idempotencyKey = 'farm-registration:'.$farm->getKey().':'.$farm->boundary_hash;
        $syncRun = $farm->syncRuns()->where('idempotency_key', $idempotencyKey)->first();

        if ($syncRun === null) {
            $syncRun = $this->recordSyncRun->create(
                $farm,
                SyncType::FarmRegistration,
                $provider,
                $trigger,
                $idempotencyKey,
                $triggeredBy,
            );
        } elseif (in_array($syncRun->status, [SyncStatus::Failed, SyncStatus::Partial], true)) {
            $this->recordSyncRun->retry($syncRun, $trigger);
        } else {
            return $syncRun;
        }

        RegisterFarmWithProvider::dispatch(
            $farm->getKey(),
            $syncRun->getKey(),
            $farm->boundary_hash,
        )->onQueue('sync')->afterCommit();

        return $syncRun;
    }
}
