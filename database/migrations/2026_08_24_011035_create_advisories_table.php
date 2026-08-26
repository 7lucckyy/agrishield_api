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
        Schema::create('advisories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_crop_cycle_id')->nullable()->constrained('farm_crop_cycles')->nullOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('payload')->nullable();
            $table->string('severity', 20)->nullable();
            $table->string('locale', 10)->default('en');
            $table->timestampTz('observed_at')->nullable();
            $table->timestampTz('valid_from')->nullable();
            $table->timestampTz('valid_until')->nullable();
            $table->string('source', 40);
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_reference', 191)->nullable();
            $table->char('dedupe_key', 64)->unique();
            $table->timestamps();

            $table->index(['farm_id', 'type', 'observed_at'], 'idx_adv_farm_type_observed');
            $table->index(['farm_id', 'valid_until'], 'idx_adv_farm_valid');
            $table->index('severity', 'idx_adv_severity');
            $table->index('farm_crop_cycle_id', 'idx_adv_cycle');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE advisories ADD CONSTRAINT advisories_type_check CHECK (type IN ('pest_warning','disease_warning','irrigation','crop_practice','weather_action','nutrient','crop_health'))");
            DB::statement("ALTER TABLE advisories ADD CONSTRAINT advisories_severity_check CHECK (severity IS NULL OR severity IN ('info','low','medium','high','critical'))");
            DB::statement('ALTER TABLE advisories ADD CONSTRAINT advisories_validity_check CHECK (valid_until IS NULL OR valid_from IS NULL OR valid_until >= valid_from)');
            DB::statement('CREATE UNIQUE INDEX advisories_external_reference_unique ON advisories (source, external_reference) WHERE external_reference IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisories');
    }
};
