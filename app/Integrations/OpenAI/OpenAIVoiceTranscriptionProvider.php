<?php

declare(strict_types=1);

namespace App\Integrations\OpenAI;

use App\Data\VoiceTranscriptionResult;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class OpenAIVoiceTranscriptionProvider implements VoiceTranscriptionProvider
{
    public function __construct(private Factory $http) {}

    public function transcribe(string $absoluteAudioPath, string $mimeType, string $sourceLanguage): VoiceTranscriptionResult
    {
        $apiKey = (string) config('voice-assistance.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI transcription is selected, but OPENAI_API_KEY is not configured.');
        }

        $audio = file_get_contents($absoluteAudioPath);
        if ($audio === false) {
            throw new RuntimeException('The stored voice note could not be read.');
        }

        $data = [
            'model' => config('voice-assistance.openai.transcription_model'),
            'response_format' => 'json',
        ];
        if (! in_array($sourceLanguage, ['auto', 'pcm'], true)) {
            $data['language'] = $sourceLanguage;
        }

        $response = $this->http->withToken($apiKey)
            ->acceptJson()
            ->connectTimeout((int) config('voice-assistance.openai.connect_timeout'))
            ->timeout((int) config('voice-assistance.openai.read_timeout'))
            ->retry(2, 300)
            ->attach('file', $audio, basename($absoluteAudioPath), ['Content-Type' => $mimeType])
            ->post($this->endpoint('/audio/transcriptions'), $data)
            ->throw();

        $transcript = trim((string) $response->json('text'));
        if ($transcript === '') {
            throw new RuntimeException('OpenAI returned an empty voice transcript.');
        }

        return new VoiceTranscriptionResult(
            transcript: $transcript,
            reference: $response->header('x-request-id') ?: null,
        );
    }

    public function name(): string
    {
        return 'openai';
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('voice-assistance.openai.base_url'), '/').$path;
    }
}
