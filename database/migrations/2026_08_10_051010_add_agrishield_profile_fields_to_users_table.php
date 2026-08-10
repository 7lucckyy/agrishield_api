<?php

use Illuminate\Database\Migrations\Migration;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 32)->nullable()->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->timestampTz('last_login_at')->nullable()->index();
            $table->string('locale', 10)->default('en');
            $table->softDeletesTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE users ADD CONSTRAINT users_email_or_phone_required CHECK (email IS NOT NULL OR phone IS NOT NULL)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT users_email_or_phone_required');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['last_login_at']);
            $table->dropColumn([
                'phone',
                'status',
                'phone_verified_at',
                'last_login_at',
                'locale',
                'deleted_at',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
