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
        Schema::table('farm_crop_cycles', function (Blueprint $table): void {
            $table->string('variety', 120)->nullable()->after('crop_id');
            $table->string('growth_stage', 80)->nullable()->after('variety');
            $table->string('irrigation_context', 20)->nullable()->after('growth_stage');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE farm_crop_cycles ADD CONSTRAINT farm_crop_cycles_irrigation_context_check CHECK (irrigation_context IS NULL OR irrigation_context IN ('rain_fed','irrigated','mixed'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE farm_crop_cycles DROP CONSTRAINT IF EXISTS farm_crop_cycles_irrigation_context_check');
        }

        Schema::table('farm_crop_cycles', function (Blueprint $table): void {
            $table->dropColumn(['variety', 'growth_stage', 'irrigation_context']);
        });
    }
};
