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
        Schema::table('asset_finance_applications', function (Blueprint $table) {
            $table->string('acquisition_mode', 32)->default('finance_purchase')->after('asset_finance_product_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_finance_applications', function (Blueprint $table) {
            $table->dropColumn('acquisition_mode');
        });
    }
};
