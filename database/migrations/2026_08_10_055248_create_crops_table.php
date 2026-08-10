<?php

declare(strict_types=1);

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
        Schema::create('crops', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('scientific_name', 160)->nullable();
            $table->string('code', 32)->unique();
            $table->string('category', 40)->nullable();
            $table->smallInteger('default_cycle_days')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE crops ADD CONSTRAINT crops_category_check
                CHECK (category IS NULL OR category IN
                    ('cereal','pulse','oilseed','vegetable','fruit','fibre','other'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
