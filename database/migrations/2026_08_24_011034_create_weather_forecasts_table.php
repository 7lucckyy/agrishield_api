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
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->decimal('temperature_min', 5, 2)->nullable();
            $table->decimal('temperature_max', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('rainfall', 6, 2)->nullable();
            $table->decimal('rainfall_probability', 5, 2)->nullable();
            $table->decimal('wind_speed', 6, 2)->nullable();
            $table->decimal('wind_direction', 5, 2)->nullable();
            $table->decimal('cloud_cover', 5, 2)->nullable();
            $table->string('condition_code', 40)->nullable();
            $table->json('payload')->nullable();
            $table->string('source', 40);
            $table->timestampTz('fetched_at');
            $table->timestamps();

            $table->unique(['farm_id', 'source', 'forecast_date']);
            $table->index(['farm_id', 'forecast_date'], 'idx_wf_farm_date');
            $table->index('fetched_at', 'idx_wf_fetched');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE weather_forecasts ADD CONSTRAINT weather_humidity_check CHECK (humidity IS NULL OR humidity BETWEEN 0 AND 100)');
            DB::statement('ALTER TABLE weather_forecasts ADD CONSTRAINT weather_rain_probability_check CHECK (rainfall_probability IS NULL OR rainfall_probability BETWEEN 0 AND 100)');
            DB::statement('ALTER TABLE weather_forecasts ADD CONSTRAINT weather_cloud_cover_check CHECK (cloud_cover IS NULL OR cloud_cover BETWEEN 0 AND 100)');
            DB::statement('ALTER TABLE weather_forecasts ADD CONSTRAINT weather_wind_direction_check CHECK (wind_direction IS NULL OR wind_direction BETWEEN 0 AND 360)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
