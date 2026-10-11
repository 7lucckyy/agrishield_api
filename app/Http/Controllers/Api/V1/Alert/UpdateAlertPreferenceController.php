<?php

namespace App\Http\Controllers\Api\V1\Alert;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Alert\UpdateAlertPreferenceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class UpdateAlertPreferenceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateAlertPreferenceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $data['consented_at'] = ($data['consent'] ?? false) ? now() : null;
        $preference = $user->alertPreference()->updateOrCreate([], $data);

        return response()->json(['data' => $preference->only(['in_app_enabled', 'push_enabled', 'sms_enabled', 'whatsapp_enabled', 'voice_call_enabled', 'phone', 'whatsapp_phone', 'language', 'minimum_severity', 'consent'])]);
    }
}
