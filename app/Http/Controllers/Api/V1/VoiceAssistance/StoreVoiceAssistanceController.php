<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\VoiceAssistance;

use App\Actions\VoiceAssistance\CreateVoiceAssistanceRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VoiceAssistance\StoreVoiceAssistanceRequest;
use App\Http\Resources\Api\V1\VoiceAssistanceResource;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final class StoreVoiceAssistanceController extends Controller
{
    public function __invoke(StoreVoiceAssistanceRequest $request, CreateVoiceAssistanceRequest $create): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $audio */
        $audio = $request->file('audio');
        $farm = $request->filled('farm_id') ? Farm::query()->where('uuid', $request->string('farm_id')->toString())->firstOrFail() : null;
        $voiceRequest = $create->execute($user, $audio, $request->string('source_language')->toString(), $request->string('response_language')->toString(), $farm);

        return (new VoiceAssistanceResource($voiceRequest))->response()->setStatusCode(201);
    }
}
