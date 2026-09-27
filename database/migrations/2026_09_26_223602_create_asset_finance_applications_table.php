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
        Schema::create('asset_finance_applications', function (Blueprint $table) {
            $table->id();
            $uuid = $table->uuid('uuid');
            if (DB::getDriverName() === 'pgsql') {
                $uuid->default(new Expression('gen_random_uuid()'));
            }
            $uuid->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farm_id')->constrained()->restrictOnDelete();
            $table->foreignId('farm_crop_cycle_id')->nullable()->constrained('farm_crop_cycles')->nullOnDelete();
            $table->foreignId('applicant_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('asset_finance_product_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('submitted')->index();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('requested_amount', 14, 2);
            $table->text('purpose');
            $table->string('consent_channel', 32)->default('organization_portal');
            $table->string('consent_version', 32)->default('asset-access-v1');
            $table->timestampTz('consented_at');
            $table->string('partner_reference')->nullable()->index();
            $table->text('decision_note')->nullable();
            $table->timestampTz('submitted_at');
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('delivery_verified_at')->nullable();
            $table->foreignId('delivery_verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('repayment_status', 32)->default('not_started')->index();
            $table->decimal('outstanding_amount', 14, 2)->nullable();
            $table->date('next_payment_due_at')->nullable();
            $table->timestampTz('last_partner_sync_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'idx_asset_finance_org_status');
            $table->index(['farm_id', 'created_at'], 'idx_asset_finance_farm_created');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE asset_finance_applications ADD CONSTRAINT asset_finance_amount_check CHECK (requested_amount > 0)');
            DB::statement('ALTER TABLE asset_finance_applications ADD CONSTRAINT asset_finance_quantity_check CHECK (quantity > 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_finance_applications');
    }
};
