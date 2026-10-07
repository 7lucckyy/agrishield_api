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
        Schema::dropIfExists('referral_redemptions');

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referral_code_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('referral_code', 32)->nullable()->unique();
            $table->timestampTz('referral_code_expires_at')->nullable();
        });

        Schema::create('referral_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('referral_code', 32);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestampTz('redeemed_at');
            $table->timestamps();

            $table->index(['organization_id', 'redeemed_at']);
            $table->index('user_id');
        });
    }
};
