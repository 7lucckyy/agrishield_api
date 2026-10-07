<?php

declare(strict_types=1);

use App\Integrations\Gemini\GeminiFarmerVoiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('Gemini returns guidance from an official N-ATLaS transcription', function () {
    config()->set('voice-assistance.n_atlas.asr_endpoints.ha', 'http://127.0.0.1:8090/v1/transcriptions/ha');
    config()->set('voice-assistance.n_atlas.api_key', 'local-development-key');
    config()->set('voice-assistance.gemini.api_key', 'gemini-test-key');
    config()->set('voice-assistance.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    config()->set('voice-assistance.gemini.model', 'gemini-test');
    $audioPath = tempnam(sys_get_temp_dir(), 'voice-test-');
    file_put_contents($audioPath, 'test audio');

    Http::preventStrayRequests();
    Http::fake([
        'http://127.0.0.1:8090/v1/transcriptions/ha' => Http::response([
            'text' => 'Ganyen masar ya fara canza launi.',
        ], 200, ['x-request-id' => 'asr_123']),
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'translated_transcript' => 'My maize leaves have started changing colour.',
                            'guidance' => 'Check whether the yellowing starts on older leaves and inspect soil moisture.',
                            'safety_note' => 'This is not a confirmed diagnosis. Consult an extension worker before treatment.',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ], 200, ['x-request-id' => 'gemini_123']),
    ]);

    $result = app(GeminiFarmerVoiceProvider::class)->assist($audioPath, 'audio/webm', 'ha', 'en', 'North Field, Borno');

    expect($result->transcript)->toBe('Ganyen masar ya fara canza launi.')
        ->and($result->translatedTranscript)->toContain('maize')
        ->and($result->reference)->toBe('asr_123|gemini_123');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://127.0.0.1:8090/v1/transcriptions/ha'
        && $request->hasHeader('Authorization', 'Bearer local-development-key'));
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent'
        && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
        && str_contains((string) $request->data()['contents'][0]['parts'][0]['text'], 'Ganyen masar'));

    unlink($audioPath);
});
