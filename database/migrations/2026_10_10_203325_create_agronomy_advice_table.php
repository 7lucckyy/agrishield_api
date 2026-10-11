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
        Schema::create('agronomy_advice', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('language', 8);
            $table->text('question');
            $table->text('answer');
            $table->json('next_steps');
            $table->text('uncertainty');
            $table->text('safety_note');
            $table->string('provider', 32);
            $table->json('context_snapshot');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['farm_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agronomy_advice');
    }
};
