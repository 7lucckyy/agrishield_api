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
        Schema::create('farm_crop_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained()->restrictOnDelete();
            $table->date('planting_date')->nullable()->index();
            $table->date('expected_harvest_date')->nullable();
            $table->date('actual_harvest_date')->nullable();
            $table->string('season', 40)->nullable();
            $table->string('status', 20)->default('planned');
            $table->string('external_reference', 191)->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['farm_id', 'status'], 'idx_fcc_farm_status');
            $table->index('crop_id', 'idx_fcc_crop');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE farm_crop_cycles ADD CONSTRAINT farm_crop_cycles_status_check CHECK (status IN ('planned','active','harvested','abandoned'))");
            DB::statement('ALTER TABLE farm_crop_cycles ADD CONSTRAINT farm_crop_cycles_harvest_dates_check CHECK (expected_harvest_date IS NULL OR planting_date IS NULL OR expected_harvest_date >= planting_date)');
            DB::statement("CREATE UNIQUE INDEX uniq_active_cycle_per_farm ON farm_crop_cycles (farm_id) WHERE status = 'active'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_crop_cycles');
    }
};
