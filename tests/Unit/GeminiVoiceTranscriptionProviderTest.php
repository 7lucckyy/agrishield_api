<?php

declare(strict_types=1);

use App\Integrations\Gemini\GeminiVoiceTranscriptionProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('Gemini transcribes inline Hausa audio', function () {
    config()->set('voice-assistance.gemini.api_key', 'gemini-test-key');
    config()->set('voice-assistance.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    config()->set('voice-assistance.gemini.transcription_model', 'gemini-transcription-test');
    $audioPath = tempnam(sys_get_temp_dir(), 'gemini-audio-');
    file_put_contents($audioPath, 'audio-content');

    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-transcription-test:generateContent' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'audioTranscription' => ['text' => 'Me ya sa ganyen ya zama rawaya?'],
                ]]],
            ]],
        ], 200, ['x-request-id' => 'gemini-asr-123']),
    ]);

    $result = app(GeminiVoiceTranscriptionProvider::class)->transcribe($audioPath, 'audio/mp4', 'ha');

    expect($result->transcript)->toBe('Me ya sa ganyen ya zama rawaya?')
        ->and($result->reference)->toBe('gemini-asr-123');
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'gemini-test-key')
        && $request->data()['contents'][0]['parts'][1]['inlineData']['mimeType'] === 'audio/mp4'
        && base64_decode($request->data()['contents'][0]['parts'][1]['inlineData']['data'], true) === 'audio-content');

    unlink($audioPath);
});
