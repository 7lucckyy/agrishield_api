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
        $shouldSyncSections = array_key_exists('farm_section_ids', $data);
        $sectionIds = Arr::pull($data, 'farm_section_ids', []);
        $sectionIds = is_array($sectionIds) ? $sectionIds : [];
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
            DB::transaction(function () use ($cropCycle, $data, $sectionIds, $shouldSyncSections, $nextStatus): void {
                $lockedCycle = CropCycle::query()->lockForUpdate()->findOrFail($cropCycle->getKey());
                $effectiveStatus = $nextStatus ?? $lockedCycle->status;

                if ($effectiveStatus === CropCycleStatus::Active
                    && CropCycle::query()
                        ->where('farm_id', $lockedCycle->farm_id)
                        ->where('status', CropCycleStatus::Active)
                        ->whereKeyNot($lockedCycle->getKey())
                        ->exists()) {
                    throw new ActiveCropCycleExistsException;
                }

                $cropCycle->update($data);

                if ($shouldSyncSections) {
                    $cropCycle->sections()->sync($sectionIds);
                }
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'uniq_active_cycle_per_farm')) {
                throw new ActiveCropCycleExistsException;
            }

            throw $exception;
        }

        return $cropCycle->refresh()->load([
            'farm:id,uuid',
            'crop',
            'sections.farm:id,uuid,area_hectares',
            'sections.crop',
        ]);
    }
}
