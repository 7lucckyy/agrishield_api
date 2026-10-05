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
        Schema::table('diagnosis_requests', function (Blueprint $table) {
            $table->uuid('client_request_id')->nullable()->after('uuid');
            $table->unique(['requested_by_user_id', 'client_request_id'], 'diagnosis_user_client_request_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnosis_requests', function (Blueprint $table) {
            $table->dropUnique('diagnosis_user_client_request_unique');
            $table->dropColumn('client_request_id');
        });
    }
};
