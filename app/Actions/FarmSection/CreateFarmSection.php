<?php

declare(strict_types=1);

namespace App\Actions\FarmSection;

use App\Models\Farm;
use App\Models\FarmSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateFarmSection
{
    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, array $data): FarmSection
    {
        return DB::transaction(function () use ($farm, $data): FarmSection {
            $lockedFarm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
            $areaHectares = (float) $data['area_hectares'];
            $this->ensureAreaFits($lockedFarm, $areaHectares);

            $data['area_acres'] = $areaHectares * 2.47105381;
            $data['position'] ??= ((int) $lockedFarm->sections()->max('position')) + 1;

            return $lockedFarm->sections()
                ->create(Arr::only($data, ['name', 'crop_id', 'area_hectares', 'area_acres', 'position', 'notes']))
                ->load(['farm:id,uuid,area_hectares', 'crop']);
        });
    }

    private function ensureAreaFits(Farm $farm, float $newAreaHectares): void
    {
        if ($farm->area_hectares === null) {
            return;
        }

        $allocatedHectares = (float) $farm->sections()->sum('area_hectares');
        if ($allocatedHectares + $newAreaHectares > (float) $farm->area_hectares + 0.0001) {
            throw ValidationException::withMessages([
                'area_hectares' => 'The section area exceeds the unallocated area remaining on this farm.',
            ]);
        }
    }
}
