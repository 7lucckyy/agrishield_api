<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Sync\QueueFarmRegistration;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Models\Farm;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class RetryFailedFarmRegistrations extends Command
{
    protected $signature = 'farming:retry-failed-registrations';

    protected $description = 'Retry farm registrations that failed during the last 24 hours';

    public function handle(QueueFarmRegistration $queueFarmRegistration): int
    {
        $queued = 0;

        Farm::query()
            ->where('provider_status', ProviderStatus::Failed)
            ->where('updated_at', '>=', now()->subDay())
            ->chunkById(200, function (Collection $farms) use ($queueFarmRegistration, &$queued): void {
                foreach ($farms as $farm) {
                    DB::transaction(function () use ($farm, $queueFarmRegistration, &$queued): void {
                        $lockedFarm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
                        if ($lockedFarm->provider_status !== ProviderStatus::Failed) {
                            return;
                        }

                        $lockedFarm->provider_status = ProviderStatus::Pending;
                        $lockedFarm->save();
                        $queueFarmRegistration->execute($lockedFarm, SyncTrigger::Retry);
                        $queued++;
                    });
                }
            });

        $this->info($queued.' farm registration retries queued.');

        return self::SUCCESS;
    }
}
