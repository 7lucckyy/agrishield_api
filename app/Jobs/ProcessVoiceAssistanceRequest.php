<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\VoiceAssistanceStatus;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
        $source = Storage::disk($request->audio_disk)->readStream($request->audio_path);
        if (! is_resource($source)) {
            throw new RuntimeException('The stored voice note could not be opened.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'agrishield-voice-');
        if ($temporaryPath === false) {
            fclose($source);
            throw new RuntimeException('A temporary voice file could not be created.');
        }

        $extension = match ($request->audio_mime) {
            'audio/mpeg' => 'mp3',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/webm', 'video/webm' => 'webm',
            default => 'bin',
        };
        $audioPath = $temporaryPath.'.'.$extension;

        try {
            if (! rename($temporaryPath, $audioPath)) {
                throw new RuntimeException('A temporary voice file could not be prepared.');
            }

            $destination = fopen($audioPath, 'wb');
            if (! is_resource($destination)) {
                throw new RuntimeException('A temporary voice file could not be opened.');
            }
            try {
                if (stream_copy_to_stream($source, $destination) === false) {
                    throw new RuntimeException('The stored voice note could not be copied.');
                }
            } finally {
                fclose($destination);
            }

            $result = $provider->assist(
                $audioPath,
                $request->audio_mime,
                $request->source_language,
                $request->response_language,
                $farmContext,
            );
        } finally {
            fclose($source);
            if (is_file($audioPath)) {
                unlink($audioPath);
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

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
