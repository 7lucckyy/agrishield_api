<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->postgisIsInstalled() || Schema::hasColumn('farms', 'boundary')) {
            return;
        }

        DB::statement('ALTER TABLE farms ADD COLUMN boundary geography(Geometry,4326) NULL');
        DB::statement('CREATE INDEX idx_farms_boundary_gist ON farms USING GIST (boundary)');
        DB::statement('UPDATE farms SET boundary = ST_SetSRID(ST_GeomFromGeoJSON(boundary_geojson::text), 4326)::geography WHERE boundary_geojson IS NOT NULL');
        DB::statement('ALTER TABLE farms ADD CONSTRAINT farms_boundary_valid_check CHECK (boundary IS NULL OR ST_IsValid(boundary::geometry))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->postgisIsInstalled() || ! Schema::hasColumn('farms', 'boundary')) {
            return;
        }

        DB::statement('ALTER TABLE farms DROP CONSTRAINT IF EXISTS farms_boundary_valid_check');
        DB::statement('DROP INDEX IF EXISTS idx_farms_boundary_gist');
        DB::statement('ALTER TABLE farms DROP COLUMN boundary');
    }

    private function postgisIsInstalled(): bool
    {
        return DB::getDriverName() === 'pgsql'
            && (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis')");
    }
};
