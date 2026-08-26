<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DiagnosisResultStatus;
use App\Enums\DiagnosisStatus;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\DiagnosisRequest;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PollDiagnosisResult implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 10;

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
        if (! in_array($diagnosis->status, [DiagnosisStatus::Submitted, DiagnosisStatus::Processing], true)) {
            return;
        }

        if ($diagnosis->expires_at?->isPast() === true) {
            $diagnosis->fill(['status' => DiagnosisStatus::Expired])->save();

            return;
        }

        $result = $provider->fetchDiagnosisResult($diagnosis);
        if ($result->status === DiagnosisResultStatus::Completed) {
            $diagnosis->fill([
                'status' => DiagnosisStatus::Completed,
                'diagnosis' => $result->diagnosis,
                'recommendation' => $result->recommendation,
                'confidence' => $result->confidence,
                'detected_labels' => $result->detectedLabels,
                'completed_at' => $result->completedAt ?? now(),
                'failure_reason' => null,
            ])->save();

            return;
        }

        if ($result->status === DiagnosisResultStatus::Failed) {
            $diagnosis->fill(['status' => DiagnosisStatus::Failed, 'failure_reason' => $result->failureReason])->save();

            return;
        }

        $diagnosis->fill(['status' => DiagnosisStatus::Processing])->save();
        self::dispatch($diagnosis->getKey())->delay(now()->addMinutes(5));
    }
}
