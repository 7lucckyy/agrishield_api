<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Crop;

use App\Actions\Crop\UpdateCrop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Crop\UpdateCropRequest;
use App\Http\Resources\Api\V1\CropResource;
use App\Models\Crop;

final class UpdateCropController extends Controller
{
    public function __construct(private UpdateCrop $updateCrop) {}

    public function __invoke(UpdateCropRequest $request, Crop $crop): CropResource
    {
        return new CropResource(
            $this->updateCrop->execute($crop, $request->validated()),
        );
    }
}
