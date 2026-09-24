<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\VoiceAssistanceStatus;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessVoiceAssistanceRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 150;

    public function __construct(public readonly int $voiceAssistanceRequestId)
    {
        $this->onQueue('voice-assistance');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(FarmerVoiceProvider $provider): void
    {
        $request = VoiceAssistanceRequest::query()->with('farm:id,name,locality,state')->findOrFail($this->voiceAssistanceRequestId);
        if ($request->status !== VoiceAssistanceStatus::Processing) {
            return;
        }

        $farmContext = $request->farm === null
            ? null
            : collect([$request->farm->name, $request->farm->locality, $request->farm->state])->filter()->join(', ');
        $result = $provider->assist(
            Storage::disk($request->audio_disk)->path($request->audio_path),
            $request->audio_mime,
            $request->source_language,
            $request->response_language,
            $farmContext,
        );

        $request->update([
            'status' => VoiceAssistanceStatus::Completed,
            'transcript' => $result->transcript,
            'translated_transcript' => $result->translatedTranscript,
            'guidance' => $result->guidance,
            'safety_note' => $result->safetyNote,
            'provider_reference' => $result->reference,
            'failure_reason' => null,
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        VoiceAssistanceRequest::query()->whereKey($this->voiceAssistanceRequestId)->update([
            'status' => VoiceAssistanceStatus::Failed,
            'failure_reason' => 'Guidance could not be generated. Please try again or contact an extension officer.',
        ]);

        if ($exception !== null) {
            report($exception);
        }
    }
}
