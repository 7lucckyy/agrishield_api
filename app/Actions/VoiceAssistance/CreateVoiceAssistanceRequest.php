<?php

declare(strict_types=1);

namespace App\Actions\VoiceAssistance;

use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Jobs\ProcessVoiceAssistanceRequest;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final readonly class CreateVoiceAssistanceRequest
{
    public function __construct(private FarmerVoiceProvider $provider) {}

    public function execute(User $user, UploadedFile $audio, string $sourceLanguage, string $responseLanguage, ?Farm $farm = null, ?Organization $organization = null, ?string $clientRequestId = null): VoiceAssistanceRequest
    {
        $contents = file_get_contents($audio->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('The uploaded voice note could not be read.');
        }

        $checksum = hash('sha256', $contents);
        $path = null;
        try {
            /** @var array{VoiceAssistanceRequest, bool} $result */
            $result = DB::transaction(function () use ($user, $audio, $contents, $checksum, $sourceLanguage, $responseLanguage, $farm, $organization, $clientRequestId, &$path): array {
                User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                if ($clientRequestId !== null) {
                    $existing = $user->voiceAssistanceRequests()
                        ->where('client_request_id', $clientRequestId)
                        ->first();
                    if ($existing !== null) {
                        if ($existing->farm_id !== $farm?->getKey() || $existing->audio_checksum !== $checksum
                            || $existing->source_language !== $sourceLanguage || $existing->response_language !== $responseLanguage) {
                            throw new ConflictHttpException('The Idempotency-Key belongs to a different voice request.');
                        }

                        return [$existing, false];
                    }
                }

                $extension = $audio->guessExtension() ?: 'webm';
                $path = 'voice-assistance/'.$user->getKey().'/'.Str::ulid().'.'.$extension;
                if (! Storage::disk('private')->put($path, $contents, ['visibility' => 'private'])) {
                    throw new \RuntimeException('The voice note could not be stored.');
                }

                $request = new VoiceAssistanceRequest;
                $request->fill([
                    'uuid' => (string) Str::uuid(),
                    'client_request_id' => $clientRequestId,
                    'source_language' => $sourceLanguage,
                    'response_language' => $responseLanguage,
                    'audio_disk' => 'private',
                    'audio_path' => $path,
                    'audio_mime' => (string) $audio->getMimeType(),
                    'audio_size_bytes' => strlen($contents),
                    'audio_checksum' => $checksum,
                    'provider' => $this->provider->name(),
                ]);
                $request->user()->associate($user);
                $request->organization()->associate($organization ?? $farm?->organization);
                $request->farm()->associate($farm);
                $request->save();

                return [$request, true];
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $exception;
        }

        [$request, $created] = $result;
        if ($created) {
            ProcessVoiceAssistanceRequest::dispatch($request->getKey())->afterCommit();
        }

        return $request->fresh(['farm:id,uuid,name']);
    }
}
