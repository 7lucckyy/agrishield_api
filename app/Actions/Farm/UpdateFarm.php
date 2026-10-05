<?php

declare(strict_types=1);

namespace App\Actions\Farm;

use App\Actions\Sync\QueueFarmRegistration;
use App\Data\Geometry\ProcessedGeometry;
use App\Enums\FarmProviderLinkStatus;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Models\Farm;
use App\Services\Geometry\FarmSectionContainment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateFarm
{
    public function __construct(
        private QueueFarmRegistration $queueFarmRegistration,
        private FarmSectionContainment $containment,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Farm $farm, array $data, ?ProcessedGeometry $geometry): Farm
    {
        $farm = DB::transaction(function () use ($farm, $data, $geometry): Farm {
            $farm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
            $farm->fill(Arr::only($data, ['name', 'locality', 'state', 'country', 'status']));
            $boundaryChanged = $geometry !== null && $geometry->hash !== $farm->boundary_hash;

            if ($boundaryChanged) {
                if ((float) $farm->sections()->sum('area_hectares') > $geometry->areaHectares + 0.0001) {
                    throw ValidationException::withMessages([
                        'boundary_geojson' => 'The farm boundary area cannot be smaller than the allocated section area.',
                    ]);
                }

                foreach ($farm->sections()->whereNotNull('boundary_geojson')->get(['boundary_geojson']) as $section) {
                    if (! $this->containment->covers($geometry->geoJson, $section->boundary_geojson)) {
                        throw ValidationException::withMessages([
                            'boundary_geojson' => 'The farm boundary must contain every existing section boundary.',
                        ]);
                    }
                }

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

            return $farm;
        });

        return $farm->load(['owner:id,name', 'organization:id,name', 'activeCropCycle.farm:id,uuid', 'activeCropCycle.crop']);
    }
}
