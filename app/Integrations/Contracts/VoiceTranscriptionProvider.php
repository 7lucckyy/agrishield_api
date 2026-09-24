<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\Data\VoiceTranscriptionResult;

interface VoiceTranscriptionProvider
{
    public function transcribe(string $absoluteAudioPath, string $mimeType, string $sourceLanguage): VoiceTranscriptionResult;

    public function name(): string;
}
