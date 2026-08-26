<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Diagnosis;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DiagnosisRequestResource;
use App\Models\Farm;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ListDiagnosisRequestController extends Controller
{
    public function __invoke(Request $request, Farm $farm): AnonymousResourceCollection
    {
        Gate::authorize('view', $farm);
        $diagnoses = $farm->diagnosisRequests()
            ->with('farm:id,uuid')
            ->when($request->input('filter.status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('filter.crop_cycle_id'), fn ($query, $cycle) => $query->where('farm_crop_cycle_id', $cycle))
            ->latest()
            ->paginate(min(100, max(1, $request->integer('per_page', 25))));

        return DiagnosisRequestResource::collection($diagnoses);
    }
}
