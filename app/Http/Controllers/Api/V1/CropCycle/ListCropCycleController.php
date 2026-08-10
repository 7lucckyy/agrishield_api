<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CropCycle;

use App\Actions\CropCycle\ListCropCycles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CropCycle\ListCropCycleRequest;
use App\Http\Resources\Api\V1\CropCycleCollection;
use App\Models\Farm;

final class ListCropCycleController extends Controller
{
    public function __construct(private ListCropCycles $listCropCycles) {}

    public function __invoke(ListCropCycleRequest $request, Farm $farm): CropCycleCollection
    {
        return new CropCycleCollection($this->listCropCycles->execute(
            $farm,
            $request->status(),
            $request->perPage(),
            $request->url(),
        ));
    }
}
