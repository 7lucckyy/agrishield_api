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
        Schema::create('farm_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->decimal('area_hectares', 12, 4);
            $table->decimal('area_acres', 12, 4);
            $table->unsignedSmallInteger('position')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'name']);
            $table->index(['farm_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_sections');
    }
};
