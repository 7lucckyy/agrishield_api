<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\IntegrationStatus;
use App\Models\IntegrationAccount;
use Illuminate\Database\Seeder;

final class IntegrationAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'staging'])) {
            return;
        }

        IntegrationAccount::query()->updateOrCreate(
            ['provider' => 'fake'],
            [
                'label' => 'Fake Insights Provider',
                'status' => IntegrationStatus::Active,
                'credentials_ref' => null,
                'config' => ['simulated' => true],
            ],
        );
    }
}
