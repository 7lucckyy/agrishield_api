<?php

declare(strict_types=1);

namespace App\Integrations\Gemini;

use App\Data\VoiceTranscriptionResult;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
use App\Integrations\Support\ProviderExceptionMapper;
use Illuminate\Http\Client\Factory;
use Throwable;

final readonly class GeminiVoiceTranscriptionProvider implements VoiceTranscriptionProvider
{
    public function __construct(
        private Factory $http,
        private ProviderExceptionMapper $exceptionMapper,
    ) {}

    public function transcribe(string $absoluteAudioPath, string $mimeType, string $sourceLanguage): VoiceTranscriptionResult
    {
        $apiKey = (string) config('voice-assistance.gemini.api_key');
        if ($apiKey === '') {
            throw new ProviderContractViolation('Gemini is selected for voice transcription, but GEMINI_API_KEY is not configured.', 'provider_not_configured');
        }

        $audio = file_get_contents($absoluteAudioPath);
        if ($audio === false) {
            throw new ProviderContractViolation('The stored voice note could not be read.', 'voice_audio_missing');
        }

        $languageName = (string) data_get(config('voice-assistance.languages'), $sourceLanguage, $sourceLanguage);

        try {
            $response = $this->http
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('voice-assistance.gemini.connect_timeout'))
                ->timeout((int) config('voice-assistance.gemini.read_timeout'))
                ->post($this->endpoint(), [
                    'contents' => [['parts' => [
                        ['text' => "Transcribe this farming voice note exactly as spoken in {$languageName}. Return only the transcript."],
                        ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode($audio)]],
                    ]]],
                ]);
            $reference = $response->header('x-request-id');
            if ($response->failed()) {
                throw $this->exceptionMapper->fromResponse($response, $reference);
            }

            $transcript = $response->json('candidates.0.content.parts.0.audioTranscription.text')
                ?? $response->json('candidates.0.content.parts.0.text');
            if (! is_string($transcript) || trim($transcript) === '') {
                throw new ProviderContractViolation('Gemini returned an empty voice transcript.', 'provider_contract_violation', $reference);
            }
        } catch (Throwable $exception) {
            throw $this->exceptionMapper->fromThrowable($exception);
        }

        return new VoiceTranscriptionResult(trim($transcript), $reference ?: null);
    }

    public function name(): string
    {
        return 'gemini';
    }

    private function endpoint(): string
    {
        return rtrim((string) config('voice-assistance.gemini.base_url'), '/')
            .'/models/'.config('voice-assistance.gemini.transcription_model').':generateContent';
    }
}
