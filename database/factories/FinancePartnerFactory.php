<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FinancePartnerType;
use App\Models\FinancePartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancePartner>
 */
class FinancePartnerFactory extends Factory
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
            'name' => fake()->company(),
            'legal_name' => fake()->company().' Limited',
            'slug' => fake()->unique()->slug(2),
            'type' => FinancePartnerType::DepositMoneyBank,
            'financing_model' => 'Partner-led asset finance',
            'website' => fake()->url(),
            'is_active' => true,
            'metadata' => ['relationship_status' => 'proposed'],
        ];
    }
}
