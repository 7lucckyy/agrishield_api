<?php

declare(strict_types=1);

use App\Integrations\Gemini\GeminiFarmerVoiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('Gemini transcribes and translates a farmer voice note', function () {
    config()->set('voice-assistance.transcription_provider', 'gemini');
    config()->set('voice-assistance.gemini.api_key', 'gemini-test-key');
    config()->set('voice-assistance.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    config()->set('voice-assistance.gemini.transcription_model', 'gemini-transcription-test');
    config()->set('voice-assistance.gemini.model', 'gemini-guidance-test');
    $audioPath = tempnam(sys_get_temp_dir(), 'voice-test-');
    file_put_contents($audioPath, 'test audio');

    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-transcription-test:generateContent' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'audioTranscription' => ['text' => 'Ganyen masar ya fara canza launi.'],
                ]]],
            ]],
        ], 200, ['x-request-id' => 'gemini_asr_123']),
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-guidance-test:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'translated_transcript' => 'Ganyen masar ya fara canza launi.',
                            'guidance' => 'Duba danshin ƙasa da tsire-tsiren da ke kusa.',
                            'safety_note' => 'Wannan ba tabbataccen bincike ba ne. Tuntuɓi jami’in noma kafin magani.',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ], 200, ['x-request-id' => 'gemini_123']),
    ]);

    $result = app(GeminiFarmerVoiceProvider::class)->assist($audioPath, 'audio/webm', 'ha', 'ha', 'North Field, Borno');

    expect($result->transcript)->toBe('Ganyen masar ya fara canza launi.')
        ->and($result->translatedTranscript)->toContain('masar')
        ->and($result->reference)->toBe('gemini_asr_123|gemini_123');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-transcription-test:generateContent'
        && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
        && $request->data()['contents'][0]['parts'][1]['inlineData']['mimeType'] === 'audio/webm');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-guidance-test:generateContent'
        && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
        && str_contains((string) $request->data()['contents'][0]['parts'][0]['text'], 'Ganyen masar')
        && str_contains((string) $request->data()['contents'][0]['parts'][0]['text'], 'Respond in Hausa'));

    unlink($audioPath);
});
