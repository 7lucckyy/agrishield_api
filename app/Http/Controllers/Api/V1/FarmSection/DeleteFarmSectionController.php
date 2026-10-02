<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\FarmSection;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmSection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class DeleteFarmSectionController extends Controller
{
    public function __invoke(Farm $farm, FarmSection $farmSection): Response
    {
        Gate::authorize('update', $farm);
        $farmSection->delete();

        return response()->noContent();
    }
}
