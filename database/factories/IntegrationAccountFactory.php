<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CircuitState;
use App\Enums\IntegrationStatus;
use App\Models\IntegrationAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IntegrationAccount> */
final class IntegrationAccountFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'provider' => fake()->unique()->lexify('provider-????'),
            'label' => fake()->company(),
            'status' => IntegrationStatus::Active,
            'credentials_ref' => null,
            'config' => ['simulated' => true],
            'consecutive_failures' => 0,
            'circuit_state' => CircuitState::Closed,
        ];
    }

    public function fakeProvider(): static
    {
        return $this->state(fn (): array => [
            'provider' => 'fake',
            'label' => 'Fake Insights Provider',
            'status' => IntegrationStatus::Active,
            'credentials_ref' => null,
        ]);
    }
}
