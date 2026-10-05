<?php

declare(strict_types=1);

namespace App\Services\Geometry;

use App\Models\FarmSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;

final class FarmSectionGeometryWriter
{
    /** @throws JsonException */
    public function synchronizeSpatialColumn(FarmSection $farmSection): void
    {
        if (DB::getDriverName() !== 'pgsql'
            || ! Schema::hasColumn($farmSection->getTable(), 'boundary')
            || $farmSection->boundary_geojson === null) {
            return;
        }

        DB::statement(
            'UPDATE farm_sections SET boundary = ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography WHERE id = ?',
            [json_encode($farmSection->boundary_geojson, JSON_THROW_ON_ERROR), $farmSection->getKey()],
        );
    }
}
