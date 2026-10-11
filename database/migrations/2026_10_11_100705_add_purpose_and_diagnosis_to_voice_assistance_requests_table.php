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
        Schema::table('voice_assistance_requests', function (Blueprint $table) {
            $table->string('purpose', 32)->default('general');
            $table->foreignId('diagnosis_request_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['purpose', 'farm_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voice_assistance_requests', function (Blueprint $table) {
            $table->dropIndex(['purpose', 'farm_id']);
            $table->dropConstrainedForeignId('diagnosis_request_id');
            $table->dropColumn('purpose');
        });
    }
};
