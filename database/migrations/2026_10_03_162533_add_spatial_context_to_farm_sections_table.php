<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_sections', function (Blueprint $table): void {
            $table->jsonb('boundary_geojson')->nullable();
            $table->char('boundary_hash', 64)->nullable()->index();
            $table->decimal('centroid_latitude', 10, 7)->nullable();
            $table->decimal('centroid_longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('active')->index();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE farm_sections ADD CONSTRAINT farm_sections_status_check CHECK (status IN ('active','fallow','inactive','archived'))");
            DB::statement('ALTER TABLE farm_sections ADD CONSTRAINT farm_sections_centroid_latitude_check CHECK (centroid_latitude IS NULL OR centroid_latitude BETWEEN -90 AND 90)');
            DB::statement('ALTER TABLE farm_sections ADD CONSTRAINT farm_sections_centroid_longitude_check CHECK (centroid_longitude IS NULL OR centroid_longitude BETWEEN -180 AND 180)');
        }

        if ($this->postgisIsInstalled()) {
            DB::statement('ALTER TABLE farm_sections ADD COLUMN boundary geography(Geometry,4326) NULL');
            DB::statement('CREATE INDEX idx_farm_sections_boundary_gist ON farm_sections USING GIST (boundary)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE farm_sections DROP CONSTRAINT IF EXISTS farm_sections_status_check');
            DB::statement('ALTER TABLE farm_sections DROP CONSTRAINT IF EXISTS farm_sections_centroid_latitude_check');
            DB::statement('ALTER TABLE farm_sections DROP CONSTRAINT IF EXISTS farm_sections_centroid_longitude_check');
        }

        Schema::table('farm_sections', function (Blueprint $table): void {
            if (Schema::hasColumn('farm_sections', 'boundary')) {
                $table->dropColumn('boundary');
            }

            $table->dropIndex(['boundary_hash']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'boundary_geojson',
                'boundary_hash',
                'centroid_latitude',
                'centroid_longitude',
                'status',
            ]);
        });
    }

    private function postgisIsInstalled(): bool
    {
        return DB::getDriverName() === 'pgsql'
            && (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis')");
    }
};
