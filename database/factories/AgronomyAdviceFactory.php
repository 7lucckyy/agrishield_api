<?php

namespace Database\Factories;

use App\Models\AgronomyAdvice;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgronomyAdvice>
 */
class AgronomyAdviceFactory extends Factory
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
            'user_id' => User::factory(),
            'farm_id' => Farm::factory(),
            'language' => 'en',
            'question' => 'What should I inspect before planting maize?',
            'answer' => 'Check the soil, seed and local weather before planting.',
            'next_steps' => ['Inspect seed quality', 'Check the field drainage'],
            'uncertainty' => 'The field has not been inspected in person.',
            'safety_note' => 'Ask a local agronomist before applying treatments.',
            'provider' => 'fake',
            'context_snapshot' => [],
        ];
    }
}
