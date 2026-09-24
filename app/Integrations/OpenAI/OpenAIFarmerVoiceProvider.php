<?php

declare(strict_types=1);

namespace App\Integrations\OpenAI;

use App\Data\VoiceAssistanceResult;
use App\Integrations\Contracts\FarmerVoiceProvider;
use Illuminate\Http\Client\Factory;
use RuntimeException;

final readonly class OpenAIFarmerVoiceProvider implements FarmerVoiceProvider
{
    public function __construct(private Factory $http) {}

    public function assist(string $absoluteAudioPath, string $mimeType, string $sourceLanguage, string $responseLanguage, ?string $farmContext = null): VoiceAssistanceResult
    {
        $apiKey = (string) config('voice-assistance.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI is selected for voice assistance, but OPENAI_API_KEY is not configured.');
        }

        $audio = file_get_contents($absoluteAudioPath);
        if ($audio === false) {
            throw new RuntimeException('The stored voice note could not be read.');
        }

        $transcriptionRequest = $this->http->withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->retry(2, 300)
            ->attach('file', $audio, basename($absoluteAudioPath), ['Content-Type' => $mimeType]);

        $transcriptionData = [
            'model' => config('voice-assistance.openai.transcription_model'),
            'response_format' => 'json',
        ];
        if ($sourceLanguage !== 'auto') {
            $transcriptionData['language'] = $sourceLanguage;
        }

        $transcriptionResponse = $transcriptionRequest
            ->post($this->endpoint('/audio/transcriptions'), $transcriptionData)
            ->throw();
        $transcript = trim((string) $transcriptionResponse->json('text'));
        if ($transcript === '') {
            throw new RuntimeException('The voice provider returned an empty transcript.');
        }

        $languageName = (string) data_get(config('voice-assistance.languages'), $responseLanguage, $responseLanguage);
        $context = $farmContext === null ? 'No farm record was selected.' : $farmContext;
        $response = $this->http->withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->retry(2, 300)
            ->post($this->endpoint('/responses'), [
                'model' => config('voice-assistance.openai.guidance_model'),
                'instructions' => 'You are AgriShield Field Voice, a crop support assistant for Nigerian farmers. Only answer questions about crops, soil, weather and crop farming. Translate faithfully, give short practical low-risk next steps, state uncertainty, never invent a crop diagnosis, and never provide pesticide dosage. For poisoning, chemical exposure or immediate danger, advise urgent local professional help. Return only the requested JSON.',
                'input' => "Response language: {$languageName}\nFarm context: {$context}\nFarmer voice transcript: {$transcript}",
                'text' => ['format' => [
                    'type' => 'json_schema',
                    'name' => 'field_voice_guidance',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'translated_transcript' => ['type' => 'string'],
                            'guidance' => ['type' => 'string'],
                            'safety_note' => ['type' => 'string'],
                        ],
                        'required' => ['translated_transcript', 'guidance', 'safety_note'],
                        'additionalProperties' => false,
                    ],
                ]],
            ])->throw();

        $outputText = collect($response->json('output', []))
            ->flatMap(fn (array $item): array => $item['content'] ?? [])
            ->firstWhere('type', 'output_text')['text'] ?? null;
        $guidance = is_string($outputText) ? json_decode($outputText, true, flags: JSON_THROW_ON_ERROR) : null;
        if (! is_array($guidance)) {
            throw new RuntimeException('The voice provider returned an unreadable guidance response.');
        }

        return new VoiceAssistanceResult(
            transcript: $transcript,
            translatedTranscript: (string) $guidance['translated_transcript'],
            guidance: (string) $guidance['guidance'],
            safetyNote: (string) $guidance['safety_note'],
            reference: (string) $response->json('id'),
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
