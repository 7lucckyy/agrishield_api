<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\Farm;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Support\Str;

final class RecordSyncRun
{
    public function create(
        Farm $farm,
        SyncType $type,
        string $provider,
        SyncTrigger $trigger,
        ?string $idempotencyKey = null,
        ?User $triggeredBy = null,
    ): SyncRun {
        $syncRun = new SyncRun;
        $syncRun->fill([
            'uuid' => (string) Str::uuid(),
            'sync_type' => $type,
            'provider' => $provider,
            'trigger' => $trigger,
            'idempotency_key' => $idempotencyKey,
        ]);
        $syncRun->farm()->associate($farm);
        $syncRun->triggeredBy()->associate($triggeredBy);
        $syncRun->save();

        return $syncRun;
    }

    public function startAttempt(SyncRun $syncRun): SyncRun
    {
        if (in_array($syncRun->status, [SyncStatus::Failed, SyncStatus::Partial], true)) {
            $syncRun->status = SyncStatus::Pending;
            $syncRun->save();
        }

        $syncRun->status = SyncStatus::Running;
        $syncRun->started_at = now();
        $syncRun->completed_at = null;
        $syncRun->duration_ms = null;
        $syncRun->attempts++;
        $syncRun->error_code = null;
        $syncRun->error_message = null;
        $syncRun->save();

        return $syncRun;
    }

    public function retry(SyncRun $syncRun, SyncTrigger $trigger): SyncRun
    {
        if (! in_array($syncRun->status, [SyncStatus::Failed, SyncStatus::Partial], true)) {
            return $syncRun;
        }

        $syncRun->status = SyncStatus::Pending;
        $syncRun->trigger = $trigger;
        $syncRun->started_at = null;
        $syncRun->completed_at = null;
        $syncRun->duration_ms = null;
        $syncRun->error_code = null;
        $syncRun->error_message = null;
        $syncRun->save();

        return $syncRun;
    }

    public function succeed(
        SyncRun $syncRun,
        int $recordsWritten = 0,
        ?string $providerRequestId = null,
    ): SyncRun {
        $this->complete($syncRun, SyncStatus::Succeeded);
        $syncRun->records_written = $recordsWritten;
        $syncRun->provider_request_id = $providerRequestId;
        $syncRun->save();

        return $syncRun;
    }

    public function fail(
        SyncRun $syncRun,
        string $errorCode,
        string $errorMessage,
        ?string $providerRequestId = null,
    ): SyncRun {
        $this->complete($syncRun, SyncStatus::Failed);
        $syncRun->error_code = $errorCode;
        $syncRun->error_message = Str::limit($errorMessage, 2000, '');
        $syncRun->provider_request_id = $providerRequestId;
        $syncRun->save();

        return $syncRun;
    }

    public function skip(SyncRun $syncRun, string $reason): SyncRun
    {
        $this->complete($syncRun, SyncStatus::Skipped);
        $syncRun->error_code = $reason;
        $syncRun->error_message = null;
        $syncRun->save();

        return $syncRun;
    }

    private function complete(SyncRun $syncRun, SyncStatus $status): void
    {
        $completedAt = now();
        $syncRun->status = $status;
        $syncRun->completed_at = $completedAt;
        $syncRun->duration_ms = $syncRun->started_at === null
            ? 0
            : (int) $syncRun->started_at->diffInMilliseconds($completedAt);
    }
}
