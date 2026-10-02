<?php

declare(strict_types=1);

namespace App\Actions\FarmSection;

use App\Models\Farm;
use App\Models\FarmSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateFarmSection
{
    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, FarmSection $farmSection, array $data): FarmSection
    {
        return DB::transaction(function () use ($farm, $farmSection, $data): FarmSection {
            $lockedFarm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
            $section = $lockedFarm->sections()->whereKey($farmSection->getKey())->lockForUpdate()->firstOrFail();
            $areaHectares = (float) Arr::get($data, 'area_hectares', $section->area_hectares);
            $otherAllocatedHectares = (float) $lockedFarm->sections()
                ->whereKeyNot($section->getKey())
                ->sum('area_hectares');

            if ($lockedFarm->area_hectares !== null
                && $otherAllocatedHectares + $areaHectares > (float) $lockedFarm->area_hectares + 0.0001) {
                throw ValidationException::withMessages([
                    'area_hectares' => 'The section area exceeds the unallocated area remaining on this farm.',
                ]);
            }

            if (array_key_exists('area_hectares', $data)) {
                $data['area_acres'] = $areaHectares * 2.47105381;
            }

            $section->update(Arr::only($data, ['name', 'crop_id', 'area_hectares', 'area_acres', 'position', 'notes']));

            return $section->refresh()->load(['farm:id,uuid,area_hectares', 'crop']);
        });
    }
}
