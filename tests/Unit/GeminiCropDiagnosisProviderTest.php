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

test('Gemini returns a cautious crop assessment without confidence percentages', function () {
    Storage::fake('private');
    config()->set('diagnosis.gemini.api_key', 'gemini-test-key');
    config()->set('diagnosis.gemini.model', 'gemini-3.8-flash');
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
    ]);

    $provider = app(GeminiCropDiagnosisProvider::class);
    expect($provider->submitDiagnosis($diagnosis, 'test-idempotency-key')->providerCaseId)->toStartWith('gemini-');

    $result = $provider->fetchDiagnosisResult($diagnosis);

    expect($result->status)->toBe(DiagnosisResultStatus::Completed)
        ->and($result->diagnosis)->toContain('Possible diagnosis')
        ->and($result->recommendation)->toContain('Possible visual assessment for demonstration purposes. Please consult an extension worker before treatment.')
        ->and($result->confidence)->toBeNull()
        ->and($result->detectedLabels)->toBeNull()
        ->and($result->providerRequestId)->toBe('gemini-123');
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent')
        && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
        && $request['contents'][0]['parts'][1]['inlineData']['data'] === base64_encode('crop-image-bytes'));
});
