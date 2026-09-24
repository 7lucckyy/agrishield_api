<?php

declare(strict_types=1);

namespace App\Data;

final readonly class VoiceAssistanceResult
{
    public function __construct(
        public string $transcript,
        public string $translatedTranscript,
        public string $guidance,
        public string $safetyNote,
        public ?string $reference = null,
    ) {}
}
