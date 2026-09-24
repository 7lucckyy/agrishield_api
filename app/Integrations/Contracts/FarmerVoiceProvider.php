<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\Data\VoiceAssistanceResult;

interface FarmerVoiceProvider
{
    public function assist(string $absoluteAudioPath, string $mimeType, string $sourceLanguage, string $responseLanguage, ?string $farmContext = null): VoiceAssistanceResult;

    public function name(): string;
}
