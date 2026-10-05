<?php

declare(strict_types=1);

namespace App\Actions\FarmSection;

use App\Data\Geometry\ProcessedGeometry;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Services\Geometry\FarmSectionContainment;
use App\Services\Geometry\FarmSectionGeometryWriter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateFarmSection
{
    public function __construct(
        private FarmSectionGeometryWriter $geometryWriter,
        private FarmSectionContainment $containment,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(
        Farm $farm,
        FarmSection $farmSection,
        array $data,
        ?ProcessedGeometry $geometry = null,
    ): FarmSection {
        return DB::transaction(function () use ($farm, $farmSection, $data, $geometry): FarmSection {
            $lockedFarm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
            $section = $lockedFarm->sections()->whereKey($farmSection->getKey())->lockForUpdate()->firstOrFail();
            $areaHectares = $geometry !== null
                ? $geometry->areaHectares
                : (float) Arr::get($data, 'area_hectares', $section->area_hectares);
            $otherAllocatedHectares = (float) $lockedFarm->sections()
                ->whereKeyNot($section->getKey())
                ->sum('area_hectares');

            if ($lockedFarm->area_hectares !== null
                && $otherAllocatedHectares + $areaHectares > (float) $lockedFarm->area_hectares + 0.0001) {
                throw ValidationException::withMessages([
                    'area_hectares' => 'The section area exceeds the unallocated area remaining on this farm.',
                ]);
            }

            if ($geometry !== null) {
                if (! $this->containment->covers($lockedFarm->boundary_geojson, $geometry->geoJson)) {
                    throw ValidationException::withMessages([
                        'boundary_geojson' => 'The section boundary must be contained within the farm boundary.',
                    ]);
                }

                $data['boundary_geojson'] = $geometry->geoJson;
                $data['boundary_hash'] = $geometry->hash;
                $data['centroid_latitude'] = $geometry->centroidLatitude;
                $data['centroid_longitude'] = $geometry->centroidLongitude;
                $data['area_hectares'] = $areaHectares;
            }

            if (array_key_exists('area_hectares', $data)) {
                $data['area_acres'] = $areaHectares * 2.47105381;
            }

            $section->update(Arr::only($data, [
                'name',
                'crop_id',
                'area_hectares',
                'area_acres',
                'position',
                'notes',
                'boundary_geojson',
                'boundary_hash',
                'centroid_latitude',
                'centroid_longitude',
                'status',
            ]));
            $this->geometryWriter->synchronizeSpatialColumn($section);

            return $section->refresh()->load(['farm:id,uuid,area_hectares', 'crop', 'cropCycles']);
        });
    }
}
