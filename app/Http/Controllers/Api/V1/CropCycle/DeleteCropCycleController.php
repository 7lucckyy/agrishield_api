<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CropCycle;

use App\Actions\CropCycle\DeleteCropCycle;
use App\Http\Controllers\Controller;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class DeleteCropCycleController extends Controller
{
    public function __construct(private DeleteCropCycle $deleteCropCycle) {}

    public function __invoke(Farm $farm, CropCycle $cropCycle): Response
    {
        Gate::authorize('view', $farm);
        Gate::authorize('delete', $cropCycle);
        $this->deleteCropCycle->execute($cropCycle);

        return response()->noContent();
    }
}
