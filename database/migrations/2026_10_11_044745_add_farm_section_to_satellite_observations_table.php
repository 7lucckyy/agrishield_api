<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('satellite_observations', function (Blueprint $table) {
            $table->foreignId('farm_section_id')->nullable()->after('farm_id')->constrained('farm_sections')->nullOnDelete();
            $table->dropUnique('obs_farm_metric_captured_unique');
            $table->unique(['farm_id', 'farm_section_id', 'metric_type', 'captured_at', 'source'], 'obs_section_metric_captured_unique');
            $table->index(['farm_id', 'farm_section_id', 'metric_type', 'captured_at'], 'idx_obs_section_metric_captured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satellite_observations', function (Blueprint $table) {
            $table->dropIndex('idx_obs_section_metric_captured');
            $table->dropUnique('obs_section_metric_captured_unique');
            $table->unique(['farm_id', 'metric_type', 'captured_at', 'source'], 'obs_farm_metric_captured_unique');
            $table->dropConstrainedForeignId('farm_section_id');
        });
    }
};
