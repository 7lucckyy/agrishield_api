<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voice_assistance_requests', function (Blueprint $table): void {
            $table->uuid('client_request_id')->nullable()->after('uuid');
            $table->unique(['user_id', 'client_request_id'], 'voice_user_client_request_unique');
        });
    }

    public function down(): void
    {
        Schema::table('voice_assistance_requests', function (Blueprint $table): void {
            $table->dropUnique('voice_user_client_request_unique');
            $table->dropColumn('client_request_id');
        });
    }
};
