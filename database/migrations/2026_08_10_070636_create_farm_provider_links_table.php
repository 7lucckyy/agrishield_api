<?php

declare(strict_types=1);

use App\Enums\FarmProviderLinkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_provider_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_account_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40);
            $table->string('provider_farm_id', 191);
            $table->string('status', 20)->default(FarmProviderLinkStatus::Pending->value);
            $table->char('boundary_hash', 64)->nullable();
            $table->jsonb('provider_metadata')->nullable();
            $table->timestampTz('registered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampsTz();

            $table->unique(['farm_id', 'integration_account_id'], 'uniq_fpl_farm_account');
            $table->unique(['provider', 'provider_farm_id'], 'uniq_fpl_provider_farm');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE farm_provider_links ADD CONSTRAINT fpl_status_check CHECK (status IN ('pending','registered','failed','stale'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_provider_links');
    }
};
