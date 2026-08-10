<?php

declare(strict_types=1);

namespace App\Actions\Farm;

use App\Actions\Sync\QueueFarmRegistration;
use App\Data\Geometry\ProcessedGeometry;
use App\Enums\FarmProviderLinkStatus;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Models\Farm;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateFarm
{
    public function __construct(private QueueFarmRegistration $queueFarmRegistration) {}

    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, array $data, ?ProcessedGeometry $geometry): Farm
    {
        DB::transaction(function () use ($farm, $data, $geometry): void {
            $farm->fill(Arr::only($data, ['name', 'locality', 'state', 'country', 'status']));
            $boundaryChanged = $geometry !== null && $geometry->hash !== $farm->boundary_hash;

            if ($boundaryChanged) {
                $farm->boundary_geojson = $geometry->geoJson;
                $farm->boundary_hash = $geometry->hash;
                $farm->area_hectares = $geometry->areaHectares;
                $farm->area_acres = $geometry->areaAcres;
                $farm->centroid_latitude = $geometry->centroidLatitude;
                $farm->centroid_longitude = $geometry->centroidLongitude;
                $farm->provider_status = ProviderStatus::Pending;
            }

            $farm->save();

            if ($boundaryChanged) {
                $farm->providerLinks()->update(['status' => FarmProviderLinkStatus::Stale]);
                $this->queueFarmRegistration->execute($farm, SyncTrigger::Event);
            }
        });

        return $farm->load(['owner:id,name', 'organization:id,name', 'activeCropCycle.farm:id,uuid', 'activeCropCycle.crop']);
    }
}
