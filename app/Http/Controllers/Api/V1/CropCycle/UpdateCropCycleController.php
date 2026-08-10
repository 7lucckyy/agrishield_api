<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CropCycle;

use App\Actions\CropCycle\UpdateCropCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CropCycle\UpdateCropCycleRequest;
use App\Http\Resources\Api\V1\CropCycleResource;
use App\Models\CropCycle;
use App\Models\Farm;

final class UpdateCropCycleController extends Controller
{
    public function __construct(private UpdateCropCycle $updateCropCycle) {}

    public function __invoke(UpdateCropCycleRequest $request, Farm $farm, CropCycle $cropCycle): CropCycleResource
    {
        return new CropCycleResource($this->updateCropCycle->execute($cropCycle, $request->validated()));
    }
}
