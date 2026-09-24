<?php

declare(strict_types=1);

use App\Integrations\OpenAI\OpenAIFarmerVoiceProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('the OpenAI voice provider transcribes and returns structured field guidance', function () {
    config()->set('voice-assistance.openai.api_key', 'test-key');
    $audioPath = tempnam(sys_get_temp_dir(), 'voice-test-');
    file_put_contents($audioPath, 'test audio');

    Http::fake([
        '*/audio/transcriptions' => Http::response(['text' => 'My maize leaves are yellow.']),
        '*/responses' => Http::response([
            'id' => 'resp_123',
            'output' => [[
                'content' => [[
                    'type' => 'output_text',
                    'text' => json_encode([
                        'translated_transcript' => 'My maize leaves are yellow.',
                        'guidance' => 'Check where the yellowing starts and compare soil moisture.',
                        'safety_note' => 'Confirm any treatment with an extension officer.',
                    ], JSON_THROW_ON_ERROR),
                ]],
            ]],
        ]),
    ]);

    $result = app(OpenAIFarmerVoiceProvider::class)->assist($audioPath, 'audio/webm', 'en', 'ha', 'North Field, Borno');

    expect($result->transcript)->toBe('My maize leaves are yellow.')
        ->and($result->reference)->toBe('resp_123')
        ->and($result->guidance)->toContain('soil moisture');
    Http::assertSentCount(2);
    unlink($audioPath);
});
