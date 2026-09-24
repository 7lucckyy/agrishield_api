<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\VoiceAssistance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VoiceAssistanceResource;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Support\Facades\Gate;

final class ShowVoiceAssistanceController extends Controller
{
    public function __invoke(VoiceAssistanceRequest $voiceAssistanceRequest): VoiceAssistanceResource
    {
        Gate::authorize('view', $voiceAssistanceRequest);

        return new VoiceAssistanceResource($voiceAssistanceRequest->load('farm:id,uuid,name'));
    }
}
