<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Diagnosis;

use App\Actions\Diagnosis\CreateDiagnosisRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Diagnosis\StoreDiagnosisRequest;
use App\Http\Resources\Api\V1\DiagnosisRequestResource;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class StoreDiagnosisRequestController extends Controller
{
    public function __invoke(StoreDiagnosisRequest $request, Farm $farm, CreateDiagnosisRequest $createDiagnosisRequest): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $image */
        $image = $request->file('image');
        $clientRequestId = $request->header('Idempotency-Key');
        if ($clientRequestId !== null && ! Str::isUuid($clientRequestId)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'The Idempotency-Key header must be a valid UUID.',
            ]);
        }
        $diagnosis = $createDiagnosisRequest->execute($farm, $user, $image, $request->safe()->except('image'), $clientRequestId);
        $diagnosis->load('farm:id,uuid');

        return (new DiagnosisRequestResource($diagnosis))->response()->setStatusCode(202);
    }
}
