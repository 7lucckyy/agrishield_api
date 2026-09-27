<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetCategory;
use App\Models\AssetFinanceProduct;
use App\Models\FinancePartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetFinanceProduct>
 */
class AssetFinanceProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finance_partner_id' => FinancePartner::factory(),
            'name' => fake()->unique()->words(3, true),
            'category' => fake()->randomElement(AssetCategory::cases()),
            'financing_structure' => 'Partner terms subject to approval',
            'currency' => 'NGN',
            'minimum_amount' => 100000,
            'maximum_amount' => 5000000,
            'maximum_tenor_months' => 24,
            'eligibility_summary' => 'Registered crop farm with an active season and partner-required identity checks.',
            'is_active' => true,
        ];
    }
}
