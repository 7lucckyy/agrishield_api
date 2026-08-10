<?php

declare(strict_types=1);

namespace App\Actions\CropCycle;

use App\Enums\CropCycleStatus;
use App\Exceptions\ActiveCropCycleExistsException;
use App\Exceptions\InvalidTransitionException;
use App\Models\CropCycle;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateCropCycle
{
    /** @param array<string, mixed> $data */
    public function execute(CropCycle $cropCycle, array $data): CropCycle
    {
        $requestedStatus = Arr::get($data, 'status');
        $nextStatus = is_string($requestedStatus) ? CropCycleStatus::from($requestedStatus) : null;

        if ($nextStatus !== null
            && $nextStatus !== $cropCycle->status
            && ! $cropCycle->status->canTransitionTo($nextStatus)) {
            throw new InvalidTransitionException;
        }

        if ($nextStatus === CropCycleStatus::Harvested
            && ! Arr::get($data, 'actual_harvest_date', $cropCycle->actual_harvest_date)) {
            throw new InvalidTransitionException('An actual harvest date is required to harvest a crop cycle.');
        }

        try {
            DB::transaction(function () use ($cropCycle, $data): void {
                $cropCycle->update($data);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'uniq_active_cycle_per_farm')) {
                throw new ActiveCropCycleExistsException;
            }

            throw $exception;
        }

        return $cropCycle->refresh()->load(['farm:id,uuid', 'crop']);
    }
}
