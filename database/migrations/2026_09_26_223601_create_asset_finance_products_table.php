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
        Schema::create('asset_finance_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_partner_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 48)->index();
            $table->string('financing_structure', 100);
            $table->char('currency', 3)->default('NGN');
            $table->decimal('minimum_amount', 14, 2)->nullable();
            $table->decimal('maximum_amount', 14, 2)->nullable();
            $table->decimal('minimum_deposit_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('maximum_tenor_months')->nullable();
            $table->text('eligibility_summary');
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['finance_partner_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_finance_products');
    }
};
