<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\FarmLedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FarmLedgerEntry>
 */
class FarmLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'farm_id' => Farm::factory(),
            'user_id' => User::factory(),
            'entry_type' => 'expense',
            'category' => 'inputs',
            'occurred_on' => today(),
            'description' => 'Fertiliser purchase',
            'quantity' => 2,
            'unit' => 'bag',
            'unit_price' => 45000,
            'amount' => 90000,
            'source' => 'manual',
        ];
    }
}
