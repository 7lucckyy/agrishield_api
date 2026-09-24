<?php

declare(strict_types=1);

namespace App\Integrations\OpenAI;

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

final readonly class OpenAICropDiagnosisProvider implements CropDiagnosisProvider
{
    public function __construct(
        private Factory $http,
        private ProviderExceptionMapper $exceptionMapper,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission
    {
        if (! Storage::disk($request->image_disk)->exists($request->image_path)) {
            throw new ProviderContractViolation(
                'The crop image is no longer available for screening.',
                'diagnosis_image_missing',
            );
        }

        return new DiagnosisSubmission(
            providerCaseId: 'openai-'.Str::substr($idempotencyKey, 0, 32),
            submittedAt: CarbonImmutable::now(),
            expectedBy: CarbonImmutable::now(),
        );
    }

    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult
    {
        $apiKey = (string) config('diagnosis.openai.api_key');
        if ($apiKey === '') {
            throw new ProviderContractViolation(
                'OpenAI is selected for crop screening, but OPENAI_API_KEY is not configured.',
                'provider_not_configured',
            );
        }

        try {
            $image = Storage::disk($request->image_disk)->get($request->image_path);
            $request->loadMissing(['farm.activeCropCycle.crop:id,name', 'cropCycle.crop:id,name', 'requestedBy:id,locale']);

            $response = $this->http->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) config('diagnosis.openai.connect_timeout'))
                ->timeout((int) config('diagnosis.openai.read_timeout'))
                ->post($this->endpoint('/responses'), [
                    'model' => config('diagnosis.openai.model'),
                    'store' => false,
                    'instructions' => $this->instructions(),
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $this->context($request),
                            ],
                            [
                                'type' => 'input_image',
                                'image_url' => 'data:'.$request->image_mime.';base64,'.base64_encode($image),
                                'detail' => 'high',
                            ],
                        ],
                    ]],
                    'text' => ['format' => [
                        'type' => 'json_schema',
                        'name' => 'crop_health_screening',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'is_crop_image' => ['type' => 'boolean'],
                                'crop' => ['type' => 'string'],
                                'possible_condition' => ['type' => 'string'],
                                'confidence' => ['type' => 'number'],
                                'visual_signs' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'recommendation' => ['type' => 'string'],
                                'needs_expert_review' => ['type' => 'boolean'],
                                'safety_note' => ['type' => 'string'],
                            ],
                            'required' => ['is_crop_image', 'crop', 'possible_condition', 'confidence', 'visual_signs', 'recommendation', 'needs_expert_review', 'safety_note'],
                            'additionalProperties' => false,
                        ],
                    ]],
                ]);

            $providerRequestId = $response->header('x-request-id') ?: $response->json('id');
            if ($response->failed()) {
                throw $this->exceptionMapper->fromResponse($response, is_string($providerRequestId) ? $providerRequestId : null);
            }

            $screening = $this->structuredOutput($response->json('output', []), is_string($providerRequestId) ? $providerRequestId : null);
            $condition = trim((string) $screening['possible_condition']);
            $crop = trim((string) $screening['crop']);
            $confidence = max(0.0, min(1.0, (float) $screening['confidence']));
            $isCropImage = (bool) $screening['is_crop_image'];
            $visualSigns = $screening['visual_signs'] ?? [];
            $signs = collect(is_array($visualSigns) ? $visualSigns : [])
                ->filter(fn (mixed $sign): bool => is_string($sign) && trim($sign) !== '')
                ->map(fn (string $sign): string => trim($sign))
                ->values();
            $reviewNote = (bool) $screening['needs_expert_review'] ? ' Expert review is recommended.' : '';
            $recommendation = trim((string) $screening['recommendation']).' '.trim((string) $screening['safety_note']).$reviewNote;

            return new DiagnosisResult(
                status: DiagnosisResultStatus::Completed,
                diagnosis: $isCropImage ? $condition : 'The image does not clearly show a crop symptom.',
                recommendation: trim(($signs->isEmpty() ? '' : 'Visible signs: '.$signs->join('; ').'. ').$recommendation),
                confidence: $confidence,
                detectedLabels: $this->labels($crop, $condition, $confidence, $isCropImage),
                completedAt: CarbonImmutable::now(),
                providerRequestId: is_string($providerRequestId) ? $providerRequestId : null,
            );
        } catch (Throwable $exception) {
            throw $this->exceptionMapper->fromThrowable($exception);
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('diagnosis.openai.base_url'), '/').$path;
    }

    private function instructions(): string
    {
        return 'You are AgriShield Crop Screening, a cautious visual decision-support tool for crop teams in Northern Nigeria. Assess only visible crop symptoms in the supplied image. Never claim laboratory certainty, invent signs, recommend pesticide products or provide chemical dosages. If the image is unclear or is not a crop, say so and request a better image. Prefer low-risk inspection, isolation and extension-worker next steps. Keep every text field in the requested response language and return only the requested JSON.';
    }

    private function context(DiagnosisRequest $request): string
    {
        $cropCycle = $request->cropCycle()->with('crop:id,name')->first();
        $crop = $cropCycle?->crop->name ?? $request->farm->activeCropCycle?->crop->name ?? 'Not recorded';
        $locale = $request->requestedBy->locale;
        $language = (string) data_get(config('voice-assistance.languages'), $locale, $locale);
        $location = collect([$request->farm->locality, $request->farm->state, $request->farm->country])->filter()->join(', ');

        return implode("\n", [
            'Task: Screen this crop image for visible signs of disease, pest damage, nutrient stress or environmental stress.',
            'Recorded crop: '.$crop,
            'Farm location: '.($location !== '' ? $location : 'Not recorded'),
            'Field note: '.($request->note ?: 'No field note supplied'),
            'Response language: '.$language,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredOutput(mixed $output, ?string $providerRequestId): array
    {
        $outputText = collect(is_array($output) ? $output : [])
            ->flatMap(fn (mixed $item): array => is_array($item) && is_array($item['content'] ?? null) ? $item['content'] : [])
            ->first(fn (mixed $content): bool => is_array($content) && ($content['type'] ?? null) === 'output_text');

        if (! is_array($outputText) || ! is_string($outputText['text'] ?? null)) {
            throw new ProviderContractViolation('The crop screening provider returned no structured result.', 'provider_contract_violation', $providerRequestId);
        }

        $decoded = json_decode($outputText['text'], true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new ProviderContractViolation('The crop screening provider returned an unreadable result.', 'provider_contract_violation', $providerRequestId);
        }

        return $decoded;
    }

    /** @return list<array{label: string, confidence: float}> */
    private function labels(string $crop, string $condition, float $confidence, bool $isCropImage): array
    {
        if (! $isCropImage) {
            return [['label' => 'image_not_suitable', 'confidence' => $confidence]];
        }

        return collect([$crop, $condition])
            ->filter(fn (string $label): bool => $label !== '')
            ->unique()
            ->map(fn (string $label): array => ['label' => Str::lower($label), 'confidence' => $confidence])
            ->values()
            ->all();
    }
}
