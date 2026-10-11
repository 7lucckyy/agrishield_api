<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Insight;

use App\Enums\MetricType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Insight\StoreSectionNdviObservationRequest;
use App\Models\Farm;
use App\Models\FarmSection;
use Illuminate\Http\JsonResponse;

final class StoreSectionNdviObservationController extends Controller
{
    public function __invoke(StoreSectionNdviObservationRequest $request, Farm $farm, FarmSection $farmSection): JsonResponse
    {
        $observation = $farm->satelliteObservations()->create([
            ...$request->validated(),
            'farm_section_id' => $farmSection->getKey(),
            'metric_type' => MetricType::Ndvi,
            'unit' => 'index',
            'fetched_at' => now(),
        ]);

        return response()->json(['data' => [
            'id' => $observation->getKey(),
            'section_id' => $farmSection->getKey(),
            'captured_at' => $observation->captured_at->toISOString(),
        ]], 201);
    }
}
