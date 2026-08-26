<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sync;

use App\Actions\Sync\QueueInsightSync;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\TriggerFarmSyncRequest;
use App\Models\Farm;
use App\Models\User;
use App\Support\ApiErrorResponse;
use Illuminate\Http\JsonResponse;

final class TriggerFarmSyncController extends Controller
{
    public function __invoke(TriggerFarmSyncRequest $request, Farm $farm, QueueInsightSync $queueInsightSync): JsonResponse
    {
        if ($farm->provider_status !== ProviderStatus::Registered) {
            return ApiErrorResponse::make($request, 'The farm is not registered with the provider.', 'farm_not_registered', 409);
        }

        /** @var User $user */
        $user = $request->user();
        $runs = [];
        foreach ($request->syncTypes() as $type) {
            $idempotencyKey = $request->header('Idempotency-Key');
            $run = $queueInsightSync->execute(
                $farm,
                $type,
                SyncTrigger::Manual,
                $user,
                $idempotencyKey === null ? null : $idempotencyKey.':'.$type->value,
            );
            $runs[] = ['id' => $run->uuid, 'sync_type' => $type->value, 'status' => $run->status->value];
        }

        return response()->json(['data' => ['runs' => $runs, 'queued_at' => now()->toISOString()]], 202);
    }
}
