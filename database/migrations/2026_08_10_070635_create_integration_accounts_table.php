<?php

declare(strict_types=1);

use App\Enums\CircuitState;
use App\Enums\IntegrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->unique();
            $table->string('label', 120)->nullable();
            $table->string('status', 20)->default(IntegrationStatus::Inactive->value);
            $table->string('credentials_ref', 191)->nullable();
            $table->jsonb('config')->nullable();
            $table->timestampTz('last_success_at')->nullable();
            $table->timestampTz('last_failure_at')->nullable();
            $table->smallInteger('consecutive_failures')->default(0);
            $table->string('circuit_state', 12)->default(CircuitState::Closed->value);
            $table->timestampTz('circuit_opened_at')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE integration_accounts ADD CONSTRAINT integration_status_check CHECK (status IN ('active','inactive','error'))");
            DB::statement("ALTER TABLE integration_accounts ADD CONSTRAINT integration_circuit_check CHECK (circuit_state IN ('closed','open','half_open'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_accounts');
    }
};
