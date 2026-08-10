<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FarmProviderLinkStatus;
use App\Models\Farm;
use App\Models\FarmProviderLink;
use App\Models\IntegrationAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<FarmProviderLink> */
final class FarmProviderLinkFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'integration_account_id' => IntegrationAccount::factory(),
            'provider' => 'fake',
            'provider_farm_id' => 'fake-farm-'.Str::lower(Str::random(24)),
            'status' => FarmProviderLinkStatus::Registered,
            'boundary_hash' => fn (array $attributes): ?string => Farm::query()->find($attributes['farm_id'])?->boundary_hash,
            'provider_metadata' => ['simulated' => true],
            'registered_at' => now(),
        ];
    }
}
