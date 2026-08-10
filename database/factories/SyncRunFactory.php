<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SyncStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Models\Farm;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SyncRun> */
final class SyncRunFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'farm_id' => Farm::factory(),
            'sync_type' => SyncType::FarmRegistration,
            'status' => SyncStatus::Pending,
            'provider' => 'fake',
            'trigger' => SyncTrigger::Event,
            'attempts' => 0,
            'records_written' => 0,
            'idempotency_key' => null,
        ];
    }
}
