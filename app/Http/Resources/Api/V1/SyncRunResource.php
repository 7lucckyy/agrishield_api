<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\GlobalRole;
use App\Models\SyncRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class SyncRunResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SyncRun) {
            throw new LogicException('SyncRunResource requires a SyncRun model.');
        }

        $syncRun = $this->resource;

        return [
            'id' => $syncRun->uuid,
            'sync_type' => $syncRun->sync_type->value,
            'status' => $syncRun->status->value,
            'trigger' => $syncRun->trigger->value,
            'started_at' => $syncRun->started_at?->toISOString(),
            'completed_at' => $syncRun->completed_at?->toISOString(),
            'duration_ms' => $syncRun->duration_ms,
            'attempts' => $syncRun->attempts,
            'records_written' => $syncRun->records_written,
            'error_code' => $syncRun->error_code,
            'error_message' => $this->when(
                $request->user()?->hasRole(GlobalRole::PlatformAdmin->value) === true,
                $syncRun->error_message,
            ),
        ];
    }
}
