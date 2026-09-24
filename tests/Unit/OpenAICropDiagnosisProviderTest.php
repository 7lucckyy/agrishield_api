<?php

declare(strict_types=1);

use App\Enums\DiagnosisResultStatus;
use App\Integrations\OpenAI\OpenAICropDiagnosisProvider;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

test('the OpenAI crop provider returns a cautious structured image screening', function () {
    Storage::fake('private');
    config()->set('diagnosis.openai.api_key', 'test-key');
    $user = User::factory()->create(['locale' => 'ha']);
    $farm = Farm::factory()->for($user, 'owner')->create(['state' => 'Kano']);
    $diagnosis = DiagnosisRequest::factory()->for($farm)->for($user, 'requestedBy')->create([
        'image_path' => 'diagnosis/test-leaf.jpg',
        'image_mime' => 'image/jpeg',
        'note' => 'Yellow marks on maize leaves',
    ]);
    Storage::disk('private')->put($diagnosis->image_path, 'sanitised-image');

    Http::fake([
        '*/responses' => Http::response([
            'id' => 'resp_crop_123',
            'output' => [[
                'content' => [[
                    'type' => 'output_text',
                    'text' => json_encode([
                        'is_crop_image' => true,
                        'crop' => 'Maize',
                        'possible_condition' => 'Possible maize streak symptoms',
                        'confidence' => 0.78,
                        'visual_signs' => ['yellow streaking along the leaf'],
                        'recommendation' => 'Inspect nearby plants and isolate visibly affected material.',
                        'needs_expert_review' => true,
                        'safety_note' => 'Confirm the cause with an extension officer before treatment.',
                    ], JSON_THROW_ON_ERROR),
                ]],
            ]],
        ], headers: ['x-request-id' => 'req_crop_123']),
    ]);

    $provider = app(OpenAICropDiagnosisProvider::class);
    $submission = $provider->submitDiagnosis($diagnosis, $diagnosis->image_checksum);
    $result = $provider->fetchDiagnosisResult($diagnosis);

    expect($submission->expectedBy?->lessThanOrEqualTo(now()))->toBeTrue()
        ->and($result->status)->toBe(DiagnosisResultStatus::Completed)
        ->and($result->diagnosis)->toBe('Possible maize streak symptoms')
        ->and($result->confidence)->toBe(0.78)
        ->and($result->recommendation)->toContain('Expert review is recommended')
        ->and($result->providerRequestId)->toBe('req_crop_123');

    Http::assertSent(function ($request): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.openai.com/v1/responses'
            && $payload['store'] === false
            && str_contains($payload['input'][0]['content'][0]['text'], 'Response language: Hausa')
            && str_starts_with($payload['input'][0]['content'][1]['image_url'], 'data:image/jpeg;base64,');
    });
});
