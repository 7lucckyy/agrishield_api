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
    public function __construct(private Factory $http, private ProviderExceptionMapper $exceptionMapper) {}

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
            $response = $this->http->acceptJson()->asJson()
                ->connectTimeout((int) config('diagnosis.gemini.connect_timeout'))
                ->timeout((int) config('diagnosis.gemini.read_timeout'))
                ->post(rtrim((string) config('diagnosis.gemini.base_url'), '/').'/models/'.config('diagnosis.gemini.model').':generateContent?key='.urlencode($apiKey), [
                    'systemInstruction' => ['parts' => [['text' => 'You provide cautious crop-photo decision support. Never call a disease confirmed, never prescribe pesticides, and return JSON only.']]],
                    'contents' => [['parts' => [
                        ['text' => 'Assess visible crop symptoms. Return {"is_crop_image":boolean,"crop":string,"possible_condition":string,"confidence":number,"visual_signs":[string],"recommendation":string,"needs_expert_review":boolean,"safety_note":string}. Use low confidence when uncertain.'],
                        ['inlineData' => ['mimeType' => $request->image_mime, 'data' => base64_encode($image)]],
                    ]]],
                    'generationConfig' => ['responseMimeType' => 'application/json'],
                ]);
            $reference = $response->header('x-request-id');
            if ($response->failed()) { throw $this->exceptionMapper->fromResponse($response, $reference); }
            $text = $response->json('candidates.0.content.parts.0.text');
            $data = is_string($text) ? json_decode($text, true, flags: JSON_THROW_ON_ERROR) : null;
            if (! is_array($data)) { throw new ProviderContractViolation('Gemini returned no structured crop result.', 'provider_contract_violation', $reference); }
            $confidence = max(0.0, min(1.0, (float) ($data['confidence'] ?? 0)));
            $crop = trim((string) ($data['crop'] ?? ''));
            $condition = trim((string) ($data['possible_condition'] ?? ''));
            $isCrop = (bool) ($data['is_crop_image'] ?? false);
            $signs = collect($data['visual_signs'] ?? [])->filter('is_string')->map(fn (string $value): string => trim($value))->filter()->values()->all();

            return new DiagnosisResult(
                DiagnosisResultStatus::Completed,
                $isCrop ? ($condition !== '' ? $condition : 'Unable to confidently identify a condition.') : 'The image does not clearly show a crop symptom.',
                trim('Possible visual signs: '.implode('; ', $signs).'. '.(string) ($data['recommendation'] ?? '').' '.(string) ($data['safety_note'] ?? 'AI-assisted result; consult an extension worker if symptoms are severe or unclear.')),
                $confidence,
                array_values(array_filter([['label' => Str::lower($crop), 'confidence' => $confidence], ['label' => Str::lower($condition), 'confidence' => $confidence]], fn (array $label): bool => $label['label'] !== '')),
                CarbonImmutable::now(),
                $reference,
            );
        } catch (Throwable $exception) {
            throw $this->exceptionMapper->fromThrowable($exception);
        }
    }
}
