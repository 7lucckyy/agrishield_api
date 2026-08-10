<?php

declare(strict_types=1);

namespace App\Actions\CropCycle;

use App\Enums\CropCycleStatus;
use App\Exceptions\ActiveCropCycleExistsException;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CreateCropCycle
{
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

        return $cropCycle->load(['farm:id,uuid', 'crop']);
    }
}
