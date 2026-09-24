<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ShowVoiceAudioController extends Controller
{
    public function __invoke(Organization $organization, VoiceAssistanceRequest $voiceAssistanceRequest): StreamedResponse
    {
        Gate::authorize('viewOverview', $organization);
        abort_unless($voiceAssistanceRequest->organization_id === $organization->getKey(), 404);

        return Storage::disk($voiceAssistanceRequest->audio_disk)->response(
            $voiceAssistanceRequest->audio_path,
            'field-voice-'.$voiceAssistanceRequest->uuid,
            ['Content-Type' => $voiceAssistanceRequest->audio_mime, 'Content-Disposition' => 'inline'],
        );
    }
}
