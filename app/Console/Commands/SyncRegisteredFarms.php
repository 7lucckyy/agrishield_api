<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Sync\QueueInsightSync;
use App\Enums\FarmStatus;
use App\Enums\MetricType;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Exceptions\SyncAlreadyRunningException;
use App\Models\Farm;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('farming:sync {type : The insight sync type}')]
#[Description('Queue due insight syncs for registered farms')]
final class SyncRegisteredFarms extends Command
{
    public function handle(QueueInsightSync $queueInsightSync): int
    {
        $type = SyncType::tryFrom((string) $this->argument('type'));
        if ($type === null || ! in_array($type, $this->scheduledTypes(), true)) {
            $this->error('Unsupported scheduled insight type.');

            return self::INVALID;
        }

        $queued = 0;
        $this->eligibleQuery($type)->chunkById(500, function ($farms) use ($queueInsightSync, $type, &$queued): void {
            foreach ($farms as $farm) {
                try {
                    $queueInsightSync->execute(
                        $farm,
                        $type,
                        SyncTrigger::Schedule,
                        idempotencyKey: 'scheduled:'.$farm->getKey().':'.$type->value.':'.today()->toDateString(),
                    );
                    $queued++;
                } catch (SyncAlreadyRunningException) {
                    continue;
                }
            }
        });

        $this->info("{$queued} {$type->value} syncs queued.");

        return self::SUCCESS;
    }

    /** @return Builder<Farm> */
    private function eligibleQuery(SyncType $type): Builder
    {
        $query = Farm::query()
            ->where('status', FarmStatus::Active)
            ->where('provider_status', ProviderStatus::Registered);

        return match ($type) {
            SyncType::Weather => $query->whereDoesntHave('weatherForecasts', fn (Builder $forecasts): Builder => $forecasts->whereDate('fetched_at', today())),
            SyncType::CropHealth => $this->withoutCurrentObservation($query, MetricType::Ndvi),
            SyncType::WaterStress => $this->withoutCurrentObservation($query, MetricType::Lswi),
            SyncType::SoilMoisture => $this->withoutCurrentObservation($query, MetricType::SoilMoisture),
            SyncType::SoilHealth => $query->whereDoesntHave('satelliteObservations', fn (Builder $observations): Builder => $observations
                ->whereIn('metric_type', [MetricType::SoilNitrogen, MetricType::SoilPhosphorus, MetricType::SoilPotassium, MetricType::SoilOrganicCarbon, MetricType::SoilPh])
                ->where('captured_at', '>=', now()->subDays(120))),
            SyncType::IrrigationAdvisory => $query->whereHas('activeCropCycle')->whereDoesntHave('advisories', fn (Builder $advisories): Builder => $advisories->where('type', 'irrigation')->whereDate('observed_at', today())),
            SyncType::PestForewarning => $query->whereHas('activeCropCycle')->whereDoesntHave('advisories', fn (Builder $advisories): Builder => $advisories->where('type', 'pest_warning')->where('observed_at', '>=', now()->subWeek())),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @param  Builder<Farm>  $query
     * @return Builder<Farm>
     */
    private function withoutCurrentObservation(Builder $query, MetricType $metricType): Builder
    {
        return $query->whereDoesntHave('satelliteObservations', fn (Builder $observations): Builder => $observations
            ->where('metric_type', $metricType)
            ->where('next_expected_update_at', '>', now()));
    }

    /** @return list<SyncType> */
    private function scheduledTypes(): array
    {
        return [
            SyncType::SoilHealth,
            SyncType::Weather,
            SyncType::CropHealth,
            SyncType::WaterStress,
            SyncType::SoilMoisture,
            SyncType::IrrigationAdvisory,
            SyncType::PestForewarning,
        ];
    }
}
