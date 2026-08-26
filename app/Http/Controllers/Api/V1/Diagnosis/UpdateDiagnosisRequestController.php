<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Diagnosis;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Diagnosis\UpdateDiagnosisRequest;
use App\Http\Resources\Api\V1\DiagnosisRequestResource;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\User;

final class UpdateDiagnosisRequestController extends Controller
{
    public function __invoke(UpdateDiagnosisRequest $request, Farm $farm, DiagnosisRequest $diagnosis, RecordAuditLog $recordAuditLog): DiagnosisRequestResource
    {
        /** @var User $user */
        $user = $request->user();
        $before = $diagnosis->only(['diagnosis', 'recommendation', 'confidence']);
        $diagnosis->fill($request->validated());
        $diagnosis->reviewedBy()->associate($user);
        $diagnosis->reviewed_at = now();
        $diagnosis->save();
        $recordAuditLog->execute('diagnosis.overridden', $diagnosis, ['before' => $before, 'after' => $diagnosis->only(array_keys($before))], $user);
        $diagnosis->load('farm:id,uuid');

        return new DiagnosisRequestResource($diagnosis);
    }
}
