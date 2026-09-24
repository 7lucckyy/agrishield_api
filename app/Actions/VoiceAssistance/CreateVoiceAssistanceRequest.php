<?php

declare(strict_types=1);

namespace App\Actions\VoiceAssistance;

use App\Enums\VoiceAssistanceStatus;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class CreateVoiceAssistanceRequest
{
    public function __construct(private FarmerVoiceProvider $provider) {}

    public function execute(User $user, UploadedFile $audio, string $sourceLanguage, string $responseLanguage, ?Farm $farm = null, ?Organization $organization = null): VoiceAssistanceRequest
    {
        $contents = file_get_contents($audio->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('The uploaded voice note could not be read.');
        }

        $extension = $audio->guessExtension() ?: 'webm';
        $path = 'voice-assistance/'.$user->getKey().'/'.Str::ulid().'.'.$extension;
        Storage::disk('private')->put($path, $contents, ['visibility' => 'private']);

        $request = new VoiceAssistanceRequest;
        $request->fill([
            'uuid' => (string) Str::uuid(),
            'source_language' => $sourceLanguage,
            'response_language' => $responseLanguage,
            'audio_disk' => 'private',
            'audio_path' => $path,
            'audio_mime' => (string) $audio->getMimeType(),
            'audio_size_bytes' => strlen($contents),
            'audio_checksum' => hash('sha256', $contents),
            'provider' => $this->provider->name(),
        ]);
        $request->user()->associate($user);
        $request->organization()->associate($organization ?? $farm?->organization);
        $request->farm()->associate($farm);
        $request->save();

        try {
            $result = $this->provider->assist(
                Storage::disk('private')->path($path),
                $request->audio_mime,
                $sourceLanguage,
                $responseLanguage,
                $farm === null ? null : collect([$farm->name, $farm->locality, $farm->state])->filter()->join(', '),
            );

            $request->update([
                'status' => VoiceAssistanceStatus::Completed,
                'transcript' => $result->transcript,
                'translated_transcript' => $result->translatedTranscript,
                'guidance' => $result->guidance,
                'safety_note' => $result->safetyNote,
                'provider_reference' => $result->reference,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $request->update(['status' => VoiceAssistanceStatus::Failed, 'failure_reason' => 'Guidance could not be generated. Please try again or contact an extension officer.']);
            report($exception);
        }

        return $request->fresh(['farm:id,uuid,name']);
    }
}
