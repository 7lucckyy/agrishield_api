<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_cycle_farm_section', function (Blueprint $table): void {
            $table->foreignId('crop_cycle_id')
                ->constrained('farm_crop_cycles')
                ->cascadeOnDelete();
            $table->foreignId('farm_section_id')
                ->constrained('farm_sections')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['crop_cycle_id', 'farm_section_id']);
            $table->index(['farm_section_id', 'crop_cycle_id'], 'idx_cycle_section_reverse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_cycle_farm_section');
    }
};
