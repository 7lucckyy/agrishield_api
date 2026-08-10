<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table): void {
            $table->id();
            $uuid = $table->uuid('uuid');
            if (DB::getDriverName() === 'pgsql') {
                $uuid->default(new Expression('gen_random_uuid()'));
            }
            $uuid->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->jsonb('boundary_geojson');
            $table->char('boundary_hash', 64);
            $table->decimal('centroid_latitude', 10, 7)->nullable();
            $table->decimal('centroid_longitude', 10, 7)->nullable();
            $table->decimal('area_hectares', 12, 4)->nullable();
            $table->decimal('area_acres', 12, 4)->nullable();
            $table->string('locality')->nullable();
            $table->string('state')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('external_reference', 191)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('provider_status', 20)->default('pending')->index();
            $table->timestampTz('last_synced_at')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['owner_user_id', 'status'], 'idx_farms_owner');
            $table->index(['organization_id', 'status'], 'idx_farms_org');
            $table->index('boundary_hash', 'idx_farms_boundary_hash');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE farms ADD CONSTRAINT farms_status_check CHECK (status IN ('active','inactive','archived'))");
            DB::statement("ALTER TABLE farms ADD CONSTRAINT farms_provider_status_check CHECK (provider_status IN ('pending','registered','failed','unsupported'))");
            DB::statement('ALTER TABLE farms ADD CONSTRAINT farms_centroid_latitude_check CHECK (centroid_latitude IS NULL OR centroid_latitude BETWEEN -90 AND 90)');
            DB::statement('ALTER TABLE farms ADD CONSTRAINT farms_centroid_longitude_check CHECK (centroid_longitude IS NULL OR centroid_longitude BETWEEN -180 AND 180)');
            DB::statement('ALTER TABLE farms ADD CONSTRAINT farms_area_hectares_check CHECK (area_hectares IS NULL OR (area_hectares > 0 AND area_hectares <= 10000))');
        }

        if ($this->postgisIsInstalled()) {
            DB::statement('ALTER TABLE farms ADD COLUMN boundary geography(Geometry,4326) NULL');
            DB::statement('CREATE INDEX idx_farms_boundary_gist ON farms USING GIST (boundary)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }

    private function postgisIsInstalled(): bool
    {
        return DB::getDriverName() === 'pgsql'
            && (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'postgis')");
    }
};
