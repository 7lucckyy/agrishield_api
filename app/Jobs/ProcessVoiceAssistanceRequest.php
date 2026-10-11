<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Agronomy\CreateAgronomyAdvice;
use App\Enums\DiagnosisStatus;
use App\Enums\VoiceAssistanceStatus;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
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

    public int $timeout = 300;

    public function __construct(public readonly int $voiceAssistanceRequestId)
    {
        $this->onQueue('voice-assistance');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(FarmerVoiceProvider $provider, VoiceTranscriptionProvider $transcriber, CreateAgronomyAdvice $createAdvice): void
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

            if ($request->purpose === 'ledger') {
                $transcription = $transcriber->transcribe($audioPath, $request->audio_mime, $request->source_language);
                $request->update([
                    'status' => VoiceAssistanceStatus::Completed,
                    'transcript' => $transcription->transcript,
                    'guidance' => 'Voice note transcribed. Review the amount and category before saving it to your farm money book.',
                    'safety_note' => 'The farmer must confirm all financial details before the ledger entry is saved.',
                    'provider_reference' => $transcription->reference,
                    'failure_reason' => null,
                    'completed_at' => now(),
                ]);
            } elseif ($request->purpose === 'general') {
                $result = $provider->assist(
                    $audioPath,
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
            } else {
                $transcription = $transcriber->transcribe($audioPath, $request->audio_mime, $request->source_language);
                $context = [];
                if ($request->purpose === 'diagnosis_followup') {
                    $diagnosis = $request->diagnosisRequest;
                    if ($diagnosis === null || $diagnosis->status !== DiagnosisStatus::Completed) {
                        throw new RuntimeException('The referenced crop diagnosis is not ready for a follow-up.');
                    }
                    $context = [
                        'crop_photo_observation' => $diagnosis->diagnosis,
                        'crop_photo_previous_recommendation' => $diagnosis->recommendation,
                        'crop_photo_confidence' => $diagnosis->confidence,
                    ];
                }
                if ($request->farm === null) {
                    throw new RuntimeException('A farm is required for agronomy voice guidance.');
                }
                $advice = $createAdvice->execute($request->farm, $request->user, $transcription->transcript, $request->response_language, $context);
                $request->update([
                    'status' => VoiceAssistanceStatus::Completed,
                    'transcript' => $transcription->transcript,
                    'guidance' => $advice->answer,
                    'safety_note' => $advice->safety_note,
                    'provider' => $advice->provider,
                    'provider_reference' => $transcription->reference,
                    'failure_reason' => null,
                    'completed_at' => now(),
                ]);
            }
        } finally {
            fclose($source);
            if (is_file($audioPath)) {
                unlink($audioPath);
            }
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

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
