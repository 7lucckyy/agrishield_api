<?php

declare(strict_types=1);

namespace App\Actions\CropCycle;

use App\Actions\Sync\QueueInsightSync;
use App\Enums\CropCycleStatus;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Exceptions\ActiveCropCycleExistsException;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CreateCropCycle
{
    public function __construct(private QueueInsightSync $queueInsightSync) {}

    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, array $data): CropCycle
    {
        $data['status'] ??= CropCycleStatus::Active->value;

        try {
            $cropCycle = DB::transaction(
                fn (): CropCycle => $farm->cropCycles()->create($data),
            );
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'uniq_active_cycle_per_farm')) {
                throw new ActiveCropCycleExistsException;
            }

            throw $exception;
        }

        if ($cropCycle->status === CropCycleStatus::Active && $farm->provider_status === ProviderStatus::Registered) {
            $this->queueInsightSync->execute(
                $farm,
                SyncType::CropPractices,
                SyncTrigger::Event,
                idempotencyKey: 'crop-practices:'.$cropCycle->getKey(),
            );
        }

        return $cropCycle->load(['farm:id,uuid', 'crop']);
    }
}
