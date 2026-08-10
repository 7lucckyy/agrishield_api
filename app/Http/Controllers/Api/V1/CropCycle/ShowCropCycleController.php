<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CropCycle;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CropCycleResource;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Support\Facades\Gate;

final class ShowCropCycleController extends Controller
{
    public function __invoke(Farm $farm, CropCycle $cropCycle): CropCycleResource
    {
        Gate::authorize('view', $farm);
        Gate::authorize('view', $cropCycle);

        return new CropCycleResource($cropCycle->load(['farm:id,uuid', 'crop']));
    }
}
