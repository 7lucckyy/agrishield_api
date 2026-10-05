<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\FarmSection;

use App\Actions\FarmSection\UpdateFarmSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FarmSection\UpdateFarmSectionRequest;
use App\Http\Resources\Api\V1\FarmSectionResource;
use App\Models\Farm;
use App\Models\FarmSection;

final class UpdateFarmSectionController extends Controller
{
    public function __construct(private UpdateFarmSection $updateFarmSection) {}

    public function __invoke(UpdateFarmSectionRequest $request, Farm $farm, FarmSection $farmSection): FarmSectionResource
    {
        return new FarmSectionResource(
            $this->updateFarmSection->execute(
                $farm,
                $farmSection,
                $request->validated(),
                $request->processedGeometry(),
            ),
        );
    }
}
