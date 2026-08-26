<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DiagnosisStatus;
use App\Exceptions\Provider\ProviderException;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\DiagnosisRequest;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SubmitDiagnosisToProvider implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $diagnosisRequestId)
    {
        $this->onQueue('diagnosis');
    }

    public function uniqueId(): string
    {
        return (string) $this->diagnosisRequestId;
    }

    public function handle(FarmingInsightsProvider $provider): void
    {
        $diagnosis = DiagnosisRequest::query()->findOrFail($this->diagnosisRequestId);
        if ($diagnosis->status !== DiagnosisStatus::Queued) {
            return;
        }

        try {
            $submission = $provider->submitDiagnosis($diagnosis, $diagnosis->image_checksum);
            $diagnosis->fill([
                'status' => DiagnosisStatus::Submitted,
                'external_reference' => $submission->providerCaseId,
                'submitted_at' => $submission->submittedAt,
                'expires_at' => $submission->submittedAt->copy()->addHours(48),
                'failure_reason' => null,
            ])->save();

            PollDiagnosisResult::dispatch($diagnosis->getKey())->delay(now()->addMinute());
        } catch (ProviderException $exception) {
            if (! $exception->retryable) {
                $diagnosis->fill(['status' => DiagnosisStatus::Failed, 'failure_reason' => $exception->errorCode])->save();

                return;
            }

            throw $exception;
        }
    }
}
