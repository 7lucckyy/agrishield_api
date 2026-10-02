<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Farm;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FarmResource;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

final class ShowFarmController extends Controller
{
    public function __invoke(Farm $farm): FarmResource
    {
        Gate::authorize('view', $farm);

        return new FarmResource($farm->load([
            'owner:id,name',
            'organization:id,name',
            'activeCropCycle.farm:id,uuid',
            'activeCropCycle.crop',
            'sections' => fn (HasMany $sections): HasMany => $sections->orderBy('position')->orderBy('name'),
            'sections.farm:id,uuid,area_hectares',
            'sections.crop',
        ]));
    }
}
