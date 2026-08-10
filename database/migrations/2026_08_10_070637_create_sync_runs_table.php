<?php

declare(strict_types=1);

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('farm_id')->nullable()->constrained()->cascadeOnDelete();
            $table->nullableMorphs('syncable');
            $table->string('sync_type', 40);
            $table->string('status', 20)->default(SyncStatus::Pending->value);
            $table->string('provider', 40);
            $table->string('provider_request_id', 191)->nullable();
            $table->string('trigger', 20)->default(SyncTrigger::Schedule->value);
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->integer('records_written')->default(0);
            $table->string('error_code', 60)->nullable();
            $table->text('error_message')->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->timestampsTz();

            $table->index(['farm_id', 'sync_type', 'created_at'], 'idx_sync_farm_type_created');
            $table->index('status', 'idx_sync_status');
            $table->index('provider_request_id', 'idx_sync_provider_request');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE sync_runs ADD CONSTRAINT sync_type_check CHECK (sync_type IN ('farm_registration','soil_health','weather','crop_health','water_stress','soil_moisture','irrigation_advisory','pest_forewarning','crop_practices','diagnosis_submit','diagnosis_poll'))");
            DB::statement("ALTER TABLE sync_runs ADD CONSTRAINT sync_status_check CHECK (status IN ('pending','running','succeeded','partial','failed','skipped'))");
            DB::statement("ALTER TABLE sync_runs ADD CONSTRAINT sync_trigger_check CHECK (trigger IN ('schedule','manual','event','retry','backfill'))");
            DB::statement('CREATE UNIQUE INDEX sync_idempotency_key_unique ON sync_runs (idempotency_key) WHERE idempotency_key IS NOT NULL');
        } else {
            Schema::table('sync_runs', function (Blueprint $table): void {
                $table->unique('idempotency_key', 'sync_idempotency_key_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
