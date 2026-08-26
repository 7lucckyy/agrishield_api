<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Diagnosis;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DiagnosisRequestResource;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowDiagnosisRequestController extends Controller
{
    public function __invoke(Request $request, Farm $farm, DiagnosisRequest $diagnosis): DiagnosisRequestResource
    {
        Gate::authorize('view', $farm);
        $diagnosis->load('farm:id,uuid');

        return new DiagnosisRequestResource($diagnosis);
    }
}
