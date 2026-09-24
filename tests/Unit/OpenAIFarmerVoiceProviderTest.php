<?php

declare(strict_types=1);

use App\Integrations\OpenAI\OpenAIVoiceTranscriptionProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('the OpenAI voice provider is limited to transcription', function () {
    config()->set('voice-assistance.openai.api_key', 'test-key');
    $audioPath = tempnam(sys_get_temp_dir(), 'voice-test-');
    file_put_contents($audioPath, 'test audio');

    Http::fake([
        '*/audio/transcriptions' => Http::response(
            ['text' => 'My maize leaves are yellow.'],
            headers: ['x-request-id' => 'asr_123'],
        ),
    ]);

    $result = app(OpenAIVoiceTranscriptionProvider::class)->transcribe($audioPath, 'audio/webm', 'en');

    expect($result->transcript)->toBe('My maize leaves are yellow.')
        ->and($result->reference)->toBe('asr_123');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/audio/transcriptions'));
    unlink($audioPath);
});
