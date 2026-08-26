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
        Schema::create('diagnosis_requests', function (Blueprint $table) {
            $table->id();
            $uuid = $table->uuid('uuid');
            if (DB::getDriverName() === 'pgsql') {
                $uuid->default(new Expression('gen_random_uuid()'));
            }
            $uuid->unique();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_crop_cycle_id')->nullable()->constrained('farm_crop_cycles')->nullOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('image_disk', 40)->default('private');
            $table->string('image_path', 512);
            $table->string('image_mime', 80);
            $table->unsignedInteger('image_size_bytes');
            $table->char('image_checksum', 64);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('queued');
            $table->text('diagnosis')->nullable();
            $table->text('recommendation')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('detected_labels')->nullable();
            $table->json('provider_payload')->nullable();
            $table->string('external_reference', 191)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'status'], 'idx_diag_farm_status');
            $table->index(['status', 'submitted_at'], 'idx_diag_status_submitted');
            $table->index('requested_by_user_id', 'idx_diag_user');
            $table->index('image_checksum', 'idx_diag_checksum');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE diagnosis_requests ADD CONSTRAINT diagnosis_status_check CHECK (status IN ('queued','submitted','processing','completed','failed','expired'))");
            DB::statement('ALTER TABLE diagnosis_requests ADD CONSTRAINT diagnosis_confidence_check CHECK (confidence IS NULL OR confidence BETWEEN 0 AND 1)');
            DB::statement('CREATE UNIQUE INDEX diagnosis_external_reference_unique ON diagnosis_requests (external_reference) WHERE external_reference IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosis_requests');
    }
};
