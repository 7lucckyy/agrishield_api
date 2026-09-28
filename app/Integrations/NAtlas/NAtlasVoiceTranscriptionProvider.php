<?php

declare(strict_types=1);

namespace App\Integrations\NAtlas;

use App\Data\VoiceTranscriptionResult;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class NAtlasVoiceTranscriptionProvider implements VoiceTranscriptionProvider
{
    public function __construct(private Factory $http) {}

    public function transcribe(string $absoluteAudioPath, string $mimeType, string $sourceLanguage): VoiceTranscriptionResult
    {
        $endpoint = (string) data_get(config('voice-assistance.n_atlas.asr_endpoints'), $sourceLanguage);
        if ($endpoint === '') {
            throw new RuntimeException('N-ATLaS transcription supports English, Hausa, Yoruba and Igbo. Select the language you are speaking.');
        }

        $audio = file_get_contents($absoluteAudioPath);
        if ($audio === false) {
            throw new RuntimeException('The stored voice note could not be read.');
        }

        $request = $this->http
            ->acceptJson()
            ->withBody($audio, $mimeType)
            ->connectTimeout((int) config('voice-assistance.n_atlas.connect_timeout'))
            ->timeout((int) config('voice-assistance.n_atlas.read_timeout'))
            ->retry([300, 900]);
        $apiKey = (string) config('voice-assistance.n_atlas.api_key');
        if ($apiKey !== '') {
            $request->withToken($apiKey);
        }

        $response = $request->post($endpoint)->throw();
        $transcript = trim((string) ($response->json('text') ?? $response->json('transcript') ?? $response->json('0.text')));
        if ($transcript === '') {
            throw new RuntimeException('N-ATLaS returned an empty voice transcript.');
        }

        return new VoiceTranscriptionResult(
            transcript: $transcript,
            reference: $response->header('x-request-id') ?: null,
        );
    }

    public function name(): string
    {
        return 'n-atlas';
    }
}
