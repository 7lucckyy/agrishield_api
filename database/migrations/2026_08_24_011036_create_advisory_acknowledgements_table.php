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
        Schema::create('advisory_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('acted_at')->nullable();
            $table->string('feedback', 255)->nullable();
            $table->timestamps();

            $table->unique(['advisory_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisory_acknowledgements');
    }
};
