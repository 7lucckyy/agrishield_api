<?php

declare(strict_types=1);

namespace App\Integrations\NAtlas;

use App\Data\CropAssessmentExplanation;
use App\Exceptions\Provider\ProviderContractViolation;
use App\Integrations\Support\ProviderExceptionMapper;
use Illuminate\Http\Client\Factory;
use JsonException;

final readonly class NAtlasCropAssessmentExplainer
{
    public function __construct(
        private Factory $http,
        private ProviderExceptionMapper $exceptionMapper,
    ) {}

    /** @param list<string> $visualSigns */
    public function explain(
        string $crop,
        string $possibleCondition,
        array $visualSigns,
        string $recommendation,
        string $uncertainty,
        string $responseLanguage,
    ): CropAssessmentExplanation {
        $endpoint = (string) config('voice-assistance.n_atlas.chat_completions_url');
        if ($endpoint === '') {
            throw new ProviderContractViolation('N-ATLaS is required for farmer-language crop explanations, but NATLAS_CHAT_COMPLETIONS_URL is not configured.', 'provider_not_configured');
        }

        $request = $this->http->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('voice-assistance.n_atlas.connect_timeout'))
            ->timeout((int) config('voice-assistance.n_atlas.read_timeout'))
            ->retry([300, 900]);
        $apiKey = (string) config('voice-assistance.n_atlas.api_key');
        if ($apiKey !== '') {
            $request->withToken($apiKey);
        }

        $response = $request->post($endpoint, [
            'model' => config('voice-assistance.n_atlas.model'),
            'stream' => false,
            'temperature' => 0.1,
            'max_tokens' => 500,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You explain a cautious crop-photo assessment to a Nigerian farmer. Translate faithfully into the requested language. Do not add diagnoses, evidence, treatments, or confidence percentages. Keep the uncertainty clear and give only low-risk next steps. Return only one JSON object with exactly these string keys: diagnosis, recommendation.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'response_language' => $responseLanguage,
                        'crop' => $crop,
                        'possible_condition' => $possibleCondition,
                        'visual_signs' => $visualSigns,
                        'recommendation' => $recommendation,
                        'uncertainty' => $uncertainty,
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        $providerRequestId = $response->header('x-request-id') ?: $response->json('id');
        if ($response->failed()) {
            throw $this->exceptionMapper->fromResponse($response, is_string($providerRequestId) ? $providerRequestId : null);
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new ProviderContractViolation('N-ATLaS returned an empty crop explanation.', 'provider_contract_violation', is_string($providerRequestId) ? $providerRequestId : null);
        }

        try {
            $explanation = json_decode(trim($content), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ProviderContractViolation('N-ATLaS returned an unreadable crop explanation.', 'provider_contract_violation', is_string($providerRequestId) ? $providerRequestId : null, previous: $exception);
        }

        if (! is_array($explanation)) {
            throw new ProviderContractViolation('N-ATLaS returned an unreadable crop explanation.', 'provider_contract_violation', is_string($providerRequestId) ? $providerRequestId : null);
        }

        foreach (['diagnosis', 'recommendation'] as $key) {
            if (! is_string($explanation[$key] ?? null) || trim($explanation[$key]) === '') {
                throw new ProviderContractViolation('N-ATLaS returned an incomplete crop explanation.', 'provider_contract_violation', is_string($providerRequestId) ? $providerRequestId : null);
            }
        }

        return new CropAssessmentExplanation(
            diagnosis: trim($explanation['diagnosis']),
            recommendation: trim($explanation['recommendation']),
            providerRequestId: is_string($providerRequestId) ? $providerRequestId : null,
        );
    }
}