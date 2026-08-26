<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('satellite_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_crop_cycle_id')->nullable()->constrained('farm_crop_cycles')->nullOnDelete();
            $table->string('metric_type', 30);
            $table->decimal('value', 12, 4)->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->json('statistics')->nullable();
            $table->unsignedSmallInteger('resolution_meters')->nullable();
            $table->timestampTz('captured_at');
            $table->timestampTz('fetched_at')->useCurrent();
            $table->timestampTz('next_expected_update_at')->nullable();
            $table->string('quality_flag', 20)->nullable();
            $table->string('source', 40);
            $table->string('external_reference', 191)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'metric_type', 'captured_at', 'source'], 'obs_farm_metric_captured_unique');
            $table->index(['farm_id', 'metric_type', 'captured_at'], 'idx_obs_farm_metric_captured');
            $table->index('captured_at', 'idx_obs_captured_at');
            $table->index('farm_crop_cycle_id', 'idx_obs_farm_cycle');
            $table->index('quality_flag', 'idx_obs_quality');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE satellite_observations ADD CONSTRAINT observations_metric_type_check CHECK (metric_type IN ('ndvi','lswi','soil_moisture','soil_nitrogen','soil_phosphorus','soil_potassium','soil_organic_carbon','soil_ph'))");
            DB::statement("ALTER TABLE satellite_observations ADD CONSTRAINT observations_quality_check CHECK (quality_flag IS NULL OR quality_flag IN ('good','partial_cloud','cloudy','unreliable'))");
            DB::statement('ALTER TABLE satellite_observations ADD CONSTRAINT observations_resolution_check CHECK (resolution_meters IS NULL OR resolution_meters BETWEEN 1 AND 1000)');
            DB::statement('CREATE UNIQUE INDEX observations_external_reference_unique ON satellite_observations (source, external_reference) WHERE external_reference IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satellite_observations');
    }
};
