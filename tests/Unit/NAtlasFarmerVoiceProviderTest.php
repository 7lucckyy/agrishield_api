<?php

declare(strict_types=1);

use App\Integrations\NAtlas\NAtlasFarmerVoiceProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('N-ATLaS creates farmer guidance from an N-ATLaS transcript', function () {
    config()->set('voice-assistance.n_atlas.asr_endpoints.en', 'https://natlas.test/asr/english');
    config()->set('voice-assistance.n_atlas.chat_completions_url', 'https://natlas.test/v1/chat/completions');
    config()->set('voice-assistance.n_atlas.api_key', 'hf-test-key');
    $audioPath = tempnam(sys_get_temp_dir(), 'voice-test-');
    file_put_contents($audioPath, 'test audio');

    Http::fake([
        'https://natlas.test/asr/english' => Http::response(['text' => 'My maize leaves are yellow.'], 200, ['x-request-id' => 'asr_123']),
        'https://natlas.test/*' => Http::response([
            'id' => 'natlas_123',
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'translated_transcript' => 'Ganyen masarana suna yin rawaya.',
                        'guidance' => 'Duba danshin kasa da inda rawayar ta fara.',
                        'safety_note' => 'A tabbatar da magani tare da jami’in noma.',
                    ], JSON_THROW_ON_ERROR),
                ],
            ]],
        ]),
    ]);

    $result = app(NAtlasFarmerVoiceProvider::class)->assist($audioPath, 'audio/webm', 'en', 'ha', 'North Field, Borno');

    expect($result->transcript)->toBe('My maize leaves are yellow.')
        ->and($result->translatedTranscript)->toContain('masarana')
        ->and($result->guidance)->toContain('danshin kasa')
        ->and($result->reference)->toBe('asr_123|natlas_123');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://natlas.test/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer hf-test-key')
        && $request['model'] === 'NCAIR1/N-ATLaS');

    unlink($audioPath);
});

test('N-ATLaS rejects malformed guidance instead of silently falling back', function () {
    config()->set('voice-assistance.n_atlas.asr_endpoints.en', 'https://natlas.test/asr/english');
    config()->set('voice-assistance.n_atlas.chat_completions_url', 'https://natlas.test/v1/chat/completions');
    $audioPath = tempnam(sys_get_temp_dir(), 'voice-test-');
    file_put_contents($audioPath, 'test audio');

    Http::fake([
        'https://natlas.test/asr/english' => Http::response(['text' => 'My maize leaves are yellow.']),
        'https://natlas.test/*' => Http::response(['choices' => [['message' => ['content' => 'not-json']]]]),
    ]);

    expect(fn () => app(NAtlasFarmerVoiceProvider::class)->assist($audioPath, 'audio/webm', 'en', 'ha'))
        ->toThrow(RuntimeException::class, 'not valid JSON');

    unlink($audioPath);
});
