<?php

declare(strict_types=1);

namespace App\Integrations\Gemini;

use App\Data\VoiceAssistanceResult;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
use App\Integrations\Support\ProviderExceptionMapper;
use Illuminate\Http\Client\Factory;
use JsonException;
use Throwable;

final readonly class GeminiFarmerVoiceProvider implements FarmerVoiceProvider
{
    public function __construct(
        private Factory $http,
        private VoiceTranscriptionProvider $transcriber,
        private ProviderExceptionMapper $exceptionMapper,
    ) {}

    public function assist(string $absoluteAudioPath, string $mimeType, string $sourceLanguage, string $responseLanguage, ?string $farmContext = null): VoiceAssistanceResult
    {
        $apiKey = (string) config('voice-assistance.gemini.api_key');
        if ($apiKey === '') {
            throw new ProviderContractViolation('Gemini is selected for voice guidance, but GEMINI_API_KEY is not configured.', 'provider_not_configured');
        }

        $transcription = $this->transcriber->transcribe($absoluteAudioPath, $mimeType, $sourceLanguage);
        $languageName = (string) data_get(config('voice-assistance.response_languages'), $responseLanguage, $responseLanguage);
        $farmContext ??= 'No farm record was selected.';

        try {
            $response = $this->http
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('voice-assistance.gemini.connect_timeout'))
                ->timeout((int) config('voice-assistance.gemini.read_timeout'))
                ->post($this->endpoint(), [
                    'systemInstruction' => ['parts' => [[
                        'text' => 'You are AgriShield Field Voice, a cautious crop support assistant for Nigerian farmers. Only answer questions about crops, soil, weather, and crop farming. Give short, practical, low-risk next steps. Never invent a diagnosis, prescribe pesticides or dosage, or claim weather certainty. For poisoning, chemical exposure, or immediate danger, advise urgent local professional help. Return JSON only.',
                    ]]],
                    'contents' => [['parts' => [[
                        'text' => $this->guidancePrompt($transcription->transcript, $languageName, $farmContext),
                    ]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ]);
            $reference = $response->header('x-request-id');
            if ($response->failed()) {
                throw $this->exceptionMapper->fromResponse($response, $reference);
            }

            $content = $response->json('candidates.0.content.parts.0.text');
            if (! is_string($content) || trim($content) === '') {
                throw new ProviderContractViolation('Gemini returned an empty voice guidance response.', 'provider_contract_violation', $reference);
            }

            $guidance = $this->decodeGuidance($content, $reference);
        } catch (Throwable $exception) {
            throw $this->exceptionMapper->fromThrowable($exception);
        }

        $references = array_filter([
            $transcription->reference,
            $reference,
        ], fn (mixed $value): bool => is_string($value) && $value !== '');

        return new VoiceAssistanceResult(
            transcript: $transcription->transcript,
            translatedTranscript: $guidance['translated_transcript'],
            guidance: $guidance['guidance'],
            safetyNote: $guidance['safety_note'],
            reference: $references === [] ? null : implode('|', $references),
        );
    }

    public function name(): string
    {
        return 'gemini';
    }

    private function endpoint(): string
    {
        return rtrim((string) config('voice-assistance.gemini.base_url'), '/')
            .'/models/'.config('voice-assistance.gemini.model').':generateContent';
    }

    private function guidancePrompt(string $transcript, string $languageName, string $farmContext): string
    {
        return implode("\n", [
            "Respond in {$languageName}.",
            "Farm context: {$farmContext}",
            "Farmer voice transcript: {$transcript}",
            'Return exactly these string keys: translated_transcript, guidance, safety_note.',
            'The safety_note must state uncertainty and advise an extension worker before any treatment.',
        ]);
    }

    /** @return array{translated_transcript: string, guidance: string, safety_note: string} */
    private function decodeGuidance(string $content, ?string $reference): array
    {
        try {
            $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ProviderContractViolation('Gemini returned voice guidance that was not valid JSON.', 'provider_contract_violation', $reference, false, $exception);
        }

        if (! is_array($decoded)) {
            throw new ProviderContractViolation('Gemini returned an unreadable voice guidance response.', 'provider_contract_violation', $reference);
        }

        $required = ['translated_transcript', 'guidance', 'safety_note'];
        foreach ($required as $key) {
            if (! isset($decoded[$key]) || ! is_string($decoded[$key]) || trim($decoded[$key]) === '') {
                throw new ProviderContractViolation("Gemini voice guidance is missing a valid {$key} value.", 'provider_contract_violation', $reference);
            }
        }

        return [
            'translated_transcript' => trim($decoded['translated_transcript']),
            'guidance' => trim($decoded['guidance']),
            'safety_note' => trim($decoded['safety_note']),
        ];
    }
}
