<?php

declare(strict_types=1);

namespace App\Integrations\Gemini;

use App\DTOs\Provider\DiagnosisResult;
use App\DTOs\Provider\DiagnosisSubmission;
use App\Enums\DiagnosisResultStatus;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Integrations\Contracts\CropDiagnosisProvider;
use App\Integrations\Support\ProviderExceptionMapper;
use App\Models\DiagnosisRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class GeminiCropDiagnosisProvider implements CropDiagnosisProvider
{
    public function __construct(
        private Factory $http,
        private ProviderExceptionMapper $exceptionMapper,
    ) {}

    public function name(): string { return 'gemini'; }

    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission
    {
        if (! Storage::disk($request->image_disk)->exists($request->image_path)) {
            throw new ProviderContractViolation('The crop image is no longer available for screening.', 'diagnosis_image_missing');
        }

        return new DiagnosisSubmission('gemini-'.Str::substr($idempotencyKey, 0, 32), CarbonImmutable::now(), CarbonImmutable::now());
    }

    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult
    {
        $apiKey = (string) config('diagnosis.gemini.api_key');
        if ($apiKey === '') {
            throw new ProviderContractViolation('Gemini is selected for crop screening, but GEMINI_API_KEY is not configured.', 'provider_not_configured');
        }

        try {
            $image = Storage::disk($request->image_disk)->get($request->image_path);
            $request->loadMissing(['farm.activeCropCycle.crop:id,name', 'cropCycle.crop:id,name', 'requestedBy:id,locale']);
            $response = $this->http->withHeaders(['x-goog-api-key' => $apiKey])->acceptJson()->asJson()
                ->connectTimeout((int) config('diagnosis.gemini.connect_timeout'))
                ->timeout((int) config('diagnosis.gemini.read_timeout'))
                ->post(rtrim((string) config('diagnosis.gemini.base_url'), '/').'/models/'.config('diagnosis.gemini.model').':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => 'You provide cautious crop-photo decision support. Describe only visible evidence and possible causes. Never call a disease confirmed, never provide a confidence percentage, never prescribe pesticides or dosage, and return JSON only.']]],
                    'contents' => [['parts' => [
                        ['text' => $this->assessmentPrompt($request)],
                        ['inlineData' => ['mimeType' => $request->image_mime, 'data' => base64_encode($image)]],
                    ]]],
                    'generationConfig' => ['responseMimeType' => 'application/json'],
                ]);
            $reference = $response->header('x-request-id');
            if ($response->failed()) { throw $this->exceptionMapper->fromResponse($response, $reference); }
            $text = $response->json('candidates.0.content.parts.0.text');
            $data = is_string($text) ? json_decode($text, true, flags: JSON_THROW_ON_ERROR) : null;
            if (! is_array($data)) { throw new ProviderContractViolation('Gemini returned no structured crop result.', 'provider_contract_violation', $reference); }
            $crop = trim((string) ($data['crop'] ?? ''));
            $condition = trim((string) ($data['possible_condition'] ?? ''));
            $isCrop = (bool) ($data['is_crop_image'] ?? false);
            $visualSigns = $data['visual_signs'] ?? [];
            $signs = is_array($visualSigns) ? array_values(array_filter($visualSigns, 'is_string')) : [];
            $signs = array_values(array_filter(array_map('trim', $signs), fn (string $sign): bool => $sign !== ''));
            $uncertainty = trim((string) ($data['uncertainty'] ?? ''));
            if ($uncertainty === '') {
                throw new ProviderContractViolation('Gemini returned no uncertainty statement.', 'provider_contract_violation', $reference);
            }
            return new DiagnosisResult(
                DiagnosisResultStatus::Completed,
                $isCrop && $condition !== '' ? 'Possible diagnosis: '.$condition : 'The image does not clearly show a crop symptom.',
                trim('Visible signs: '.implode('; ', $signs).'. '.(string) ($data['recommendation'] ?? '').' '.$uncertainty.' Possible visual assessment for demonstration purposes. Please consult an extension worker before treatment.'),
                null,
                null,
                CarbonImmutable::now(),
                providerRequestId: $reference,
            );
        } catch (Throwable $exception) {
            throw $this->exceptionMapper->fromThrowable($exception);
        }
    }

    private function assessmentPrompt(DiagnosisRequest $request): string
    {
        $cropCycle = $request->cropCycle;
        $crop = $cropCycle?->crop->name ?? $request->farm->activeCropCycle?->crop->name ?? 'Not recorded';
        $location = collect([$request->farm->locality, $request->farm->state, $request->farm->country])->filter()->join(', ');

        return implode("\n", [
            'Assess visible crop symptoms only; this is a demonstration, not a confirmed diagnosis.',
            'Recorded crop: '.$crop,
            'Farm location: '.($location !== '' ? $location : 'Not recorded'),
            'Farmer note: '.($request->note ?: 'None'),
            'Return JSON with exactly these keys: is_crop_image (boolean), crop (string), possible_condition (string), visual_signs (array of strings), recommendation (string), uncertainty (string).',
            'Use cautious wording, state what cannot be determined from this image, and give only low-risk inspection steps.',
        ]);
    }
}
