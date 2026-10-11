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
        Schema::create('agricultural_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agricultural_partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('pricing_model')->default('fixed');
            $table->decimal('unit_price', 14, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->string('unit')->nullable();
            $table->decimal('minimum_quantity', 14, 3)->nullable();
            $table->decimal('available_quantity', 14, 3)->nullable();
            $table->string('state')->nullable()->index();
            $table->string('locality')->nullable();
            $table->json('requirements')->nullable();
            $table->json('fulfillment_channels')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['category', 'is_active', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agricultural_offers');
    }
};
