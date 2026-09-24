<?php

declare(strict_types=1);

namespace App\Integrations\Fake;

use App\Data\VoiceAssistanceResult;
use App\Integrations\Contracts\FarmerVoiceProvider;

final class FakeFarmerVoiceProvider implements FarmerVoiceProvider
{
    public function assist(string $absoluteAudioPath, string $mimeType, string $sourceLanguage, string $responseLanguage, ?string $farmContext = null): VoiceAssistanceResult
    {
        return new VoiceAssistanceResult(
            transcript: 'Voice note received. Connect the production voice provider to generate a transcript.',
            translatedTranscript: 'Voice note received. Connect the production voice provider to generate a translation.',
            guidance: 'Your question has been saved for an extension officer. Configure OpenAI transcription and N-ATLaS, then set VOICE_ASSISTANCE_PROVIDER=n_atlas to enable automatic field guidance.',
            safetyNote: 'Do not use this development response for pesticide dosage or emergency decisions.',
            reference: 'local-'.hash_file('sha256', $absoluteAudioPath),
        );
    }

    public function name(): string
    {
        return 'fake';
    }
}
