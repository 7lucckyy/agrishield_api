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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('referral_code', 32)->nullable()->unique();
            $table->timestampTz('referral_code_expires_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->char('country', 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
