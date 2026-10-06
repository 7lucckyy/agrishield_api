<?php

declare(strict_types=1);

use App\Enums\DiagnosisResultStatus;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Integrations\Gemini\GeminiCropDiagnosisProvider;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

function makeCropDiagnosisRequest(string $imagePath): DiagnosisRequest
{
    $farm = new Farm;
    $farm->setRelation('activeCropCycle', null);

    $diagnosis = new DiagnosisRequest([
        'image_disk' => 'private',
        'image_path' => $imagePath,
        'image_mime' => 'image/jpeg',
        'note' => 'Yellow marks on maize leaves',
    ]);
    $diagnosis->setRelation('farm', $farm);
    $diagnosis->setRelation('cropCycle', null);
    $diagnosis->setRelation('requestedBy', new User(['locale' => 'ha']));

    return $diagnosis;
}

test('Gemini assesses the image and N-ATLaS returns a cautious Hausa explanation without confidence percentages', function () {
    Storage::fake('private');
    config()->set('diagnosis.gemini.api_key', 'gemini-test-key');
    config()->set('diagnosis.gemini.model', 'gemini-3.8-flash');
    config()->set('voice-assistance.n_atlas.chat_completions_url', 'https://natlas.test/v1/chat/completions');
    config()->set('voice-assistance.n_atlas.api_key', 'natlas-test-key');
    $diagnosis = makeCropDiagnosisRequest('diagnoses/crop.jpg');
    Storage::disk('private')->put($diagnosis->image_path, 'crop-image-bytes');
    Http::preventStrayRequests();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'is_crop_image' => true,
                            'crop' => 'maize',
                            'possible_condition' => 'Possible nutrient stress',
                            'visual_signs' => ['yellowing on older leaves'],
                            'recommendation' => 'Check soil moisture and compare several plants.',
                            'uncertainty' => 'The image alone cannot distinguish nutrient stress from other causes.',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ], 200, ['x-request-id' => 'gemini-123']),
        'https://natlas.test/*' => Http::response([
            'id' => 'natlas-456',
            'choices' => [['message' => ['content' => json_encode([
                'diagnosis' => 'Mai yiyuwa akwai matsalar abinci mai gina jiki.',
                'recommendation' => 'A duba danshin kasa, sannan a kwatanta tsirrai da dama.',
            ], JSON_THROW_ON_ERROR)]]],
        ]),
    ]);

    $provider = app(GeminiCropDiagnosisProvider::class);
    expect($provider->submitDiagnosis($diagnosis, 'test-idempotency-key')->providerCaseId)->toStartWith('gemini-');

    $result = $provider->fetchDiagnosisResult($diagnosis);

    expect($result->status)->toBe(DiagnosisResultStatus::Completed)
        ->and($result->diagnosis)->toContain('Mai yiyuwa')
        ->and($result->recommendation)->toContain('Possible visual assessment for demonstration purposes. Please consult an extension worker before treatment.')
        ->and($result->confidence)->toBeNull()
        ->and($result->detectedLabels)->toBeNull()
        ->and($result->providerRequestId)->toBe('gemini-123|natlas-456');
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent')
        && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
        && $request['contents'][0]['parts'][1]['inlineData']['data'] === base64_encode('crop-image-bytes'));
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://natlas.test/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer natlas-test-key')
        && $request['model'] === 'NCAIR1/N-ATLaS'
        && str_contains($request['messages'][1]['content'], 'Hausa'));
});

test('Gemini crop screening requires N-ATLaS instead of silently returning English output', function () {
    Storage::fake('private');
    config()->set('diagnosis.gemini.api_key', 'gemini-test-key');
    config()->set('voice-assistance.n_atlas.chat_completions_url', '');
    $diagnosis = makeCropDiagnosisRequest('diagnoses/crop.jpg');
    Storage::disk('private')->put($diagnosis->image_path, 'crop-image-bytes');
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'is_crop_image' => true,
                            'crop' => 'maize',
                            'possible_condition' => 'Unclear',
                            'visual_signs' => [],
                            'recommendation' => 'Take a clearer image.',
                            'uncertainty' => 'The image is unclear.',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ]),
    ]);

    expect(fn () => app(GeminiCropDiagnosisProvider::class)->fetchDiagnosisResult($diagnosis))
        ->toThrow(ProviderContractViolation::class, 'N-ATLaS is required');
});