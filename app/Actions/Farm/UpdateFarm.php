<?php

declare(strict_types=1);

namespace App\Actions\Farm;

use App\Data\Geometry\ProcessedGeometry;
use App\Enums\ProviderStatus;
use App\Models\Farm;
use Illuminate\Support\Arr;

final class UpdateFarm
{
    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, array $data, ?ProcessedGeometry $geometry): Farm
    {
        $farm->fill(Arr::only($data, ['name', 'locality', 'state', 'country', 'status']));

        if ($geometry !== null && $geometry->hash !== $farm->boundary_hash) {
            $farm->boundary_geojson = $geometry->geoJson;
            $farm->boundary_hash = $geometry->hash;
            $farm->area_hectares = $geometry->areaHectares;
            $farm->area_acres = $geometry->areaAcres;
            $farm->centroid_latitude = $geometry->centroidLatitude;
            $farm->centroid_longitude = $geometry->centroidLongitude;
            $farm->provider_status = ProviderStatus::Pending;
        }

        $farm->save();

        return $farm->load(['owner:id,name', 'organization:id,name', 'activeCropCycle.farm:id,uuid', 'activeCropCycle.crop']);
    }
}
