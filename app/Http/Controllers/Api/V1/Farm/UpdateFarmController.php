<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Farm;

use App\Actions\Farm\UpdateFarm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Farm\UpdateFarmRequest;
use App\Http\Resources\Api\V1\FarmResource;
use App\Models\Farm;

final class UpdateFarmController extends Controller
{
    public function __construct(private UpdateFarm $updateFarm) {}

    public function __invoke(UpdateFarmRequest $request, Farm $farm): FarmResource
    {
        return new FarmResource($this->updateFarm->execute(
            $farm,
            $request->validated(),
            $request->processedGeometry(),
        ));
    }
}
