<?php

declare(strict_types=1);

use App\Integrations\NAtlas\NAtlasVoiceTranscriptionProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('N-ATLaS transcribes Hausa audio through the configured language endpoint', function () {
    config()->set('voice-assistance.n_atlas.asr_endpoints.ha', 'https://natlas.test/asr/hausa');
    config()->set('voice-assistance.n_atlas.api_key', 'natlas-key');
    Http::preventStrayRequests();
    Http::fake([
        'https://natlas.test/asr/hausa' => Http::response(['text' => 'Ganyen masar ya fara canza launi.'], 200, ['x-request-id' => 'asr-123']),
    ]);
    $audioPath = tempnam(sys_get_temp_dir(), 'natlas-audio-');
    file_put_contents($audioPath, 'audio-content');

    $result = app(NAtlasVoiceTranscriptionProvider::class)->transcribe($audioPath, 'audio/mp4', 'ha');

    expect($result->transcript)->toBe('Ganyen masar ya fara canza launi.')
        ->and($result->reference)->toBe('asr-123');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://natlas.test/asr/hausa'
        && $request->hasHeader('Authorization', 'Bearer natlas-key'));

    unlink($audioPath);
});

test('N-ATLaS rejects a language without an ASR endpoint', function () {
    expect(fn () => app(NAtlasVoiceTranscriptionProvider::class)->transcribe('/missing', 'audio/mp4', 'ff'))
        ->toThrow(RuntimeException::class, 'supports English, Hausa, Yoruba and Igbo');
});
