<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DiagnosisStatus;
use App\Models\DiagnosisRequest;
use App\Models\SatelliteObservation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('farming:purge-expired-artifacts')]
#[Description('Expire overdue diagnoses and purge retained provider payloads')]
final class PurgeExpiredArtifacts extends Command
{
    public function handle(): int
    {
        $expiredDiagnoses = DiagnosisRequest::query()
            ->whereIn('status', [DiagnosisStatus::Submitted, DiagnosisStatus::Processing])
            ->where('expires_at', '<', now())
            ->update(['status' => DiagnosisStatus::Expired]);
        $purgedPayloads = SatelliteObservation::query()
            ->whereNotNull('raw_payload')
            ->where('captured_at', '<', now()->subDays(90))
            ->update(['raw_payload' => null]);

        $this->info("{$expiredDiagnoses} diagnoses expired; {$purgedPayloads} raw payloads purged.");

        return self::SUCCESS;
    }
}
