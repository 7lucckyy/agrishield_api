<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_partners', function (Blueprint $table) {
            $table->id();
            $uuid = $table->uuid('uuid');
            if (DB::getDriverName() === 'pgsql') {
                $uuid->default(new Expression('gen_random_uuid()'));
            }
            $uuid->unique();
            $table->string('name');
            $table->string('legal_name');
            $table->string('slug')->unique();
            $table->string('type', 48)->index();
            $table->string('financing_model', 120);
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_partners');
    }
};
