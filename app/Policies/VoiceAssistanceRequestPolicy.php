<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VoiceAssistanceRequest;

class VoiceAssistanceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VoiceAssistanceRequest $voiceAssistanceRequest): bool
    {
        return $voiceAssistanceRequest->user_id === $user->getKey();
    }
}
