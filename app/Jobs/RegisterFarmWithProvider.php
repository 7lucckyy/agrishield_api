<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Sync\QueueInsightSync;
use App\Actions\Sync\RecordSyncRun;
use App\Enums\FarmProviderLinkStatus;
use App\Enums\IntegrationStatus;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Enums\SyncType;
use App\Exceptions\Provider\ProviderException;
use App\Exceptions\Provider\ProviderRejected;
use App\Exceptions\Provider\ProviderTimeout;
use App\Exceptions\Provider\ProviderUnavailable;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\Farm;
use App\Models\FarmProviderLink;
use App\Models\IntegrationAccount;
use App\Models\SyncRun;
use App\Services\Integration\CircuitBreaker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RegisterFarmWithProvider implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $farmId,
        public readonly int $syncRunId,
        public readonly string $boundaryHash,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return $this->farmId.':'.$this->boundaryHash;
    }

    public function handle(
        FarmingInsightsProvider $provider,
        CircuitBreaker $circuitBreaker,
        RecordSyncRun $recordSyncRun,
        ?QueueInsightSync $queueInsightSync = null,
    ): void {
        $farm = Farm::query()->findOrFail($this->farmId);
        $syncRun = SyncRun::query()->findOrFail($this->syncRunId);
        $integrationAccount = IntegrationAccount::query()
            ->where('provider', $provider->name())
            ->first();

        if ($integrationAccount === null) {
            $recordSyncRun->skip($syncRun, 'integration_not_configured');

            return;
        }

        if ($integrationAccount->status !== IntegrationStatus::Active) {
            $recordSyncRun->skip($syncRun, 'integration_inactive');

            return;
        }

        if ($farm->boundary_hash !== $this->boundaryHash) {
            $recordSyncRun->skip($syncRun, 'boundary_changed');

            return;
        }

        if (! $circuitBreaker->allowsRequest($provider->name())) {
            $recordSyncRun->skip($syncRun, 'circuit_open');

            return;
        }

        $recordSyncRun->startAttempt($syncRun);

        try {
            $reference = $provider->registerFarm($farm, $this->boundaryHash);

            $registered = DB::transaction(function () use ($farm, $integrationAccount, $provider, $reference): bool {
                $lockedFarm = Farm::query()->lockForUpdate()->findOrFail($farm->getKey());
                if ($lockedFarm->boundary_hash !== $this->boundaryHash) {
                    return false;
                }

                $link = FarmProviderLink::query()
                    ->whereBelongsTo($lockedFarm)
                    ->whereBelongsTo($integrationAccount)
                    ->first() ?? new FarmProviderLink;

                $link->farm()->associate($lockedFarm);
                $link->integrationAccount()->associate($integrationAccount);
                $link->fill([
                    'provider' => $provider->name(),
                    'provider_farm_id' => $reference->providerFarmId,
                    'status' => FarmProviderLinkStatus::Registered,
                    'boundary_hash' => $this->boundaryHash,
                    'provider_metadata' => $reference->metadata,
                    'registered_at' => $reference->registeredAt,
                    'last_error' => null,
                ]);
                $link->save();

                $lockedFarm->provider_status = ProviderStatus::Registered;
                $lockedFarm->save();

                return true;
            });

            $circuitBreaker->recordSuccess($provider->name());
            if ($registered) {
                $recordSyncRun->succeed($syncRun, recordsWritten: 1);
                $queueInsightSync ??= app(QueueInsightSync::class);
                foreach ($this->initialSyncTypes($farm) as $type) {
                    $queueInsightSync->execute(
                        $farm,
                        $type,
                        SyncTrigger::Event,
                        idempotencyKey: 'initial:'.$farm->getKey().':'.$this->boundaryHash.':'.$type->value,
                    );
                }
            } else {
                $recordSyncRun->fail($syncRun, 'boundary_changed', 'The farm boundary changed during registration.');
            }
        } catch (ProviderException $exception) {
            $recordSyncRun->fail(
                $syncRun,
                $exception->errorCode,
                $exception->getMessage(),
                $exception->providerRequestId,
            );

            if ($exception instanceof ProviderUnavailable || $exception instanceof ProviderTimeout) {
                $circuitBreaker->recordFailure($provider->name());
            }

            if ($exception instanceof ProviderRejected) {
                $farm->provider_status = ProviderStatus::Unsupported;
                $farm->save();

                return;
            }

            if (! $exception->retryable) {
                $farm->provider_status = ProviderStatus::Failed;
                $farm->save();

                return;
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Farm::query()
            ->whereKey($this->farmId)
            ->where('boundary_hash', $this->boundaryHash)
            ->update(['provider_status' => ProviderStatus::Failed]);

        if ($exception === null) {
            return;
        }

        SyncRun::query()
            ->whereKey($this->syncRunId)
            ->whereNull('error_code')
            ->update([
                'error_code' => 'registration_job_failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'completed_at' => now(),
            ]);
    }

    /** @return list<SyncType> */
    private function initialSyncTypes(Farm $farm): array
    {
        $types = [SyncType::SoilHealth, SyncType::Weather, SyncType::CropHealth, SyncType::WaterStress,
            SyncType::SoilMoisture, SyncType::IrrigationAdvisory, SyncType::PestForewarning];

        if ($farm->activeCropCycle()->exists()) {
            $types[] = SyncType::CropPractices;
        }

        return $types;
    }
}
