<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssetFinanceStatus;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetFinanceEvent>
 */
class AssetFinanceEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_finance_application_id' => AssetFinanceApplication::factory(),
            'recorded_by_user_id' => User::factory(),
            'event_type' => 'application_submitted',
            'status' => AssetFinanceStatus::Submitted,
            'note' => 'Application submitted with recorded consent.',
        ];
    }
}
