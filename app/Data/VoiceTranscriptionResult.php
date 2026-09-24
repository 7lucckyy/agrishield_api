<?php

declare(strict_types=1);

namespace App\Data;

final readonly class VoiceTranscriptionResult
{
    public function __construct(
        public string $transcript,
        public ?string $reference = null,
    ) {}
}
