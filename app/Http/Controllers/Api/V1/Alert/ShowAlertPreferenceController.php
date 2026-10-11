<?php

namespace App\Http\Controllers\Api\V1\Alert;

use App\Http\Controllers\Controller;
use App\Models\FarmerAlertPreference;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowAlertPreferenceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $preference = $user->alertPreference()->firstOrCreate([], [
            'phone' => $user->phone,
            'language' => $user->locale,
            'in_app_enabled' => true,
            'push_enabled' => false,
        ]);

        return response()->json(['data' => $this->payload($preference)]);
    }

    /** @return array<string, mixed> */
    private function payload(FarmerAlertPreference $preference): array
    {
        return $preference->only(['in_app_enabled', 'push_enabled', 'sms_enabled', 'whatsapp_enabled', 'voice_call_enabled', 'phone', 'whatsapp_phone', 'language', 'minimum_severity', 'consent']);
    }
}
