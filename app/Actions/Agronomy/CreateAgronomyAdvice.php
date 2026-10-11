<?php

declare(strict_types=1);

namespace App\Actions\Agronomy;

use App\Integrations\Contracts\AgronomyAdvisor;
use App\Models\AgronomyAdvice;
use App\Models\Farm;
use App\Models\User;
use App\Services\Agronomy\FarmAgronomyContextBuilder;
use Illuminate\Support\Str;

final readonly class CreateAgronomyAdvice
{
    public function __construct(
        private AgronomyAdvisor $advisor,
        private FarmAgronomyContextBuilder $contextBuilder,
    ) {}

    /** @param array<string, mixed> $additionalContext */
    public function execute(Farm $farm, User $user, string $question, string $language, array $additionalContext = []): AgronomyAdvice
    {
        $context = array_merge($this->contextBuilder->build($farm), $additionalContext);
        $result = $this->advisor->advise($question, $language, $context);
        $advice = new AgronomyAdvice([
            'uuid' => (string) Str::uuid(),
            'language' => $language,
            'question' => $question,
            'answer' => $result->answer,
            'next_steps' => $result->nextSteps,
            'uncertainty' => $result->uncertainty,
            'safety_note' => $result->safetyNote,
            'provider' => $this->advisor->name(),
            'context_snapshot' => $context,
        ]);
        $advice->user()->associate($user);
        $advice->farm()->associate($farm);
        $advice->save();

        return $advice;
    }
}
