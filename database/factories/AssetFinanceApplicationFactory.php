<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetFinanceStatus;
use App\Enums\RepaymentStatus;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetFinanceApplication>
 */
class AssetFinanceApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'organization_id' => Organization::factory(),
            'farm_id' => Farm::factory(),
            'applicant_user_id' => User::factory(),
            'asset_finance_product_id' => AssetFinanceProduct::factory(),
            'status' => AssetFinanceStatus::Submitted,
            'quantity' => 1,
            'requested_amount' => 750000,
            'purpose' => 'Improve water access for the active crop season.',
            'consent_channel' => 'organization_portal',
            'consent_version' => 'asset-access-v1',
            'consented_at' => now(),
            'submitted_at' => now(),
            'repayment_status' => RepaymentStatus::NotStarted,
        ];
    }
}
