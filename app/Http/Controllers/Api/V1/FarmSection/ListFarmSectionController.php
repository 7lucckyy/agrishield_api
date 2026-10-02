<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\FarmSection;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FarmSectionResource;
use App\Models\Farm;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ListFarmSectionController extends Controller
{
    public function __invoke(Farm $farm): AnonymousResourceCollection
    {
        Gate::authorize('view', $farm);

        $sections = $farm->sections()
            ->with(['farm:id,uuid,area_hectares', 'crop'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return FarmSectionResource::collection($sections)->additional(['meta' => [
            'allocated_hectares' => round((float) $sections->sum('area_hectares'), 4),
            'remaining_hectares' => $farm->area_hectares === null
                ? null
                : round(max(0, (float) $farm->area_hectares - (float) $sections->sum('area_hectares')), 4),
        ]]);
    }
}
