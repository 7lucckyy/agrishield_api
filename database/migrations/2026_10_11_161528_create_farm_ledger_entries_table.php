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
        Schema::create('farm_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('farm_crop_cycle_id')->nullable()->constrained('farm_crop_cycles')->nullOnDelete();
            $table->foreignId('voice_assistance_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_type')->index();
            $table->string('category')->index();
            $table->date('occurred_on')->index();
            $table->string('description');
            $table->decimal('quantity', 14, 3)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 14, 2)->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->nullable();
            $table->string('counterparty')->nullable();
            $table->string('source')->default('manual');
            $table->text('voice_transcript')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['farm_id', 'occurred_on']);
            $table->index(['farm_crop_cycle_id', 'entry_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_ledger_entries');
    }
};
