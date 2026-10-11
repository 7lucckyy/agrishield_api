<?php

declare(strict_types=1);

use App\Integrations\Gemini\GeminiAgronomyAdvisor;
use Illuminate\Support\Facades\Http;

test('Gemini agronomy adapter requests a selected Nigerian language and validates structured guidance', function () {
    config()->set('agronomy.gemini.api_key', 'test-key');
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => json_encode([
            'answer' => 'Duba ganyen masara.',
            'next_steps' => ['Duba ganyen da ya lalace.'],
            'uncertainty' => 'Ba a ga gonar ba.',
            'safety_note' => 'Tambayi jami’in noma kafin magani.',
        ], JSON_THROW_ON_ERROR)]]]]],
    ])]);

    $result = app(GeminiAgronomyAdvisor::class)->advise('Me ya faru da masara?', 'ha', ['crop' => 'Maize']);
    expect($result->answer)->toBe('Duba ganyen masara.')
        ->and($result->nextSteps)->toHaveCount(1);
    Http::assertSent(fn ($request): bool => str_contains($request->body(), 'Hausa')
        && str_contains($request->body(), 'Maize')
        && $request->hasHeader('x-goog-api-key', 'test-key'));
});
