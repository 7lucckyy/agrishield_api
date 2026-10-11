<?php

declare(strict_types=1);

namespace App\Actions\Sync;

use App\Enums\FarmSectionStatus;
use App\Integrations\Copernicus\CopernicusNdviClient;
use App\Models\Farm;
use App\Models\SatelliteObservation;

final class SyncCopernicusSections
{
    public function __construct(private readonly CopernicusNdviClient $ndvi) {}

    public function execute(Farm $farm): int
    {
        $written = 0;

        foreach ($farm->sections()->where('status', FarmSectionStatus::Active)->whereNotNull('boundary_geojson')->get() as $section) {
            $reading = $this->ndvi->latest($section->boundary_geojson, $farm->uuid.':'.$section->getKey());
            if ($reading === null) {
                continue;
            }

            $observation = SatelliteObservation::query()->firstOrNew([
                'farm_id' => $farm->getKey(),
                'farm_section_id' => $section->getKey(),
                'metric_type' => $reading->metricType,
                'captured_at' => $reading->capturedAt,
                'source' => 'copernicus',
            ]);
            $written += (int) ! $observation->exists;
            $observation->farm()->associate($farm);
            $observation->fill([
                'value' => $reading->value,
                'unit' => $reading->unit,
                'resolution_meters' => $reading->resolutionMeters,
                'statistics' => $reading->statistics,
                'quality_flag' => $reading->quality,
                'external_reference' => $reading->externalReference,
                'fetched_at' => now(),
                'next_expected_update_at' => $reading->capturedAt->copy()->addDays((int) config('farming.freshness.ndvi.cadence')),
            ]);
            $observation->save();
        }

        return $written;
    }
}
