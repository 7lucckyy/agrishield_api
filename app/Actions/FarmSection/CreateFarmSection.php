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

final class CreateFarmSection
{
    public function __construct(
        private FarmSectionGeometryWriter $geometryWriter,
        private FarmSectionContainment $containment,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, array $data, ?ProcessedGeometry $geometry = null): FarmSection
    {
        return DB::transaction(function () use ($farm, $data, $geometry): FarmSection {
            $lockedFarm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
            $areaHectares = $geometry !== null
                ? $geometry->areaHectares
                : (float) $data['area_hectares'];
            $this->ensureAreaFits($lockedFarm, $areaHectares);

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
            }

            $data['area_hectares'] = $areaHectares;
            $data['area_acres'] = $areaHectares * 2.47105381;
            $data['position'] ??= ((int) $lockedFarm->sections()->max('position')) + 1;

            $section = $lockedFarm->sections()->create(Arr::only($data, [
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

            return $section->load(['farm:id,uuid,area_hectares', 'crop', 'cropCycles']);
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
