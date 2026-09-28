<?php

declare(strict_types=1);

namespace App\Integrations\NAtlas;

use App\Data\VoiceAssistanceResult;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
use Illuminate\Http\Client\Factory;
use JsonException;
use RuntimeException;

final readonly class NAtlasFarmerVoiceProvider implements FarmerVoiceProvider
{
    public function __construct(
        private Factory $http,
        private VoiceTranscriptionProvider $transcriber,
    ) {}

    public function assist(string $absoluteAudioPath, string $mimeType, string $sourceLanguage, string $responseLanguage, ?string $farmContext = null): VoiceAssistanceResult
    {
        $endpoint = (string) config('voice-assistance.n_atlas.chat_completions_url');
        if ($endpoint === '') {
            throw new RuntimeException('N-ATLaS is selected, but NATLAS_CHAT_COMPLETIONS_URL is not configured.');
        }

        $transcription = $this->transcriber->transcribe($absoluteAudioPath, $mimeType, $sourceLanguage);
        $languageName = (string) data_get(config('voice-assistance.response_languages'), $responseLanguage, $responseLanguage);
        $context = $farmContext === null ? 'No farm record was selected.' : $farmContext;

        $request = $this->http->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('voice-assistance.n_atlas.connect_timeout'))
            ->timeout((int) config('voice-assistance.n_atlas.read_timeout'))
            ->retry(2, 500);
        $apiKey = (string) config('voice-assistance.n_atlas.api_key');
        if ($apiKey !== '') {
            $request->withToken($apiKey);
        }

        $response = $request->post($endpoint, [
            'model' => config('voice-assistance.n_atlas.model'),
            'stream' => false,
            'temperature' => 0.1,
            'max_tokens' => 900,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are AgriShield Field Voice, a cautious crop support assistant for Nigerian farmers. Only answer questions about crops, soil, weather, and crop farming. Translate faithfully into the requested language. Give short, practical, low-risk next steps and clearly state uncertainty. Never invent a crop diagnosis and never provide pesticide dosage. For poisoning, chemical exposure, or immediate danger, advise urgent local professional help. Return only one JSON object with exactly these string keys: translated_transcript, guidance, safety_note.',
                ],
                [
                    'role' => 'user',
                    'content' => "Response language: {$languageName}\nFarm context: {$context}\nFarmer voice transcript: {$transcription->transcript}",
                ],
            ],
        ])->throw();

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('N-ATLaS returned an empty guidance response.');
        }

        $guidance = $this->decodeGuidance($content);
        $reference = array_filter([
            $transcription->reference,
            $response->json('id'),
        ], fn (mixed $value): bool => is_string($value) && $value !== '');

        return new VoiceAssistanceResult(
            transcript: $transcription->transcript,
            translatedTranscript: $guidance['translated_transcript'],
            guidance: $guidance['guidance'],
            safetyNote: $guidance['safety_note'],
            reference: $reference === [] ? null : implode('|', $reference),
        );
    }

    public function name(): string
    {
        return 'n-atlas';
    }

    /** @return array{translated_transcript: string, guidance: string, safety_note: string} */
    private function decodeGuidance(string $content): array
    {
        $json = trim($content);
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $json) ?? $json;
        }

        try {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('N-ATLaS returned guidance that was not valid JSON.', previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('N-ATLaS returned an unreadable guidance response.');
        }

        $required = ['translated_transcript', 'guidance', 'safety_note'];
        foreach ($required as $key) {
            if (! isset($decoded[$key]) || ! is_string($decoded[$key]) || trim($decoded[$key]) === '') {
                throw new RuntimeException("N-ATLaS guidance is missing a valid {$key} value.");
            }
        }

        return [
            'translated_transcript' => trim($decoded['translated_transcript']),
            'guidance' => trim($decoded['guidance']),
            'safety_note' => trim($decoded['safety_note']),
        ];
    }
}
