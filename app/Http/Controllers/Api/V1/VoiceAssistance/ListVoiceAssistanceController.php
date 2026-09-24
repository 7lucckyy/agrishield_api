<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\VoiceAssistance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VoiceAssistanceResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListVoiceAssistanceController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return VoiceAssistanceResource::collection($user->voiceAssistanceRequests()->with('farm:id,uuid,name')->latest()->paginate(20));
    }
}
