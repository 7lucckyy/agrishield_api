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
        if (Schema::hasTable('agricultural_service_requests')) {
            return;
        }

        Schema::create('agricultural_service_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agricultural_offer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('farm_crop_cycle_id')->nullable()->constrained('farm_crop_cycles')->nullOnDelete();
            $table->string('request_type')->index();
            $table->string('status')->default('submitted')->index();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('budget_amount', 14, 2)->nullable();
            $table->date('requested_for')->nullable();
            $table->text('purpose')->nullable();
            $table->string('consent_version')->default('agricultural-services-v1');
            $table->timestamp('consented_at');
            $table->timestamp('submitted_at');
            $table->text('decision_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['organization_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agricultural_service_requests');
    }
};
