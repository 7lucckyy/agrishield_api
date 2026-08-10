<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Crop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CropResource;
use App\Models\Crop;
use Illuminate\Support\Facades\Gate;

final class ShowCropController extends Controller
{
    public function __invoke(Crop $crop): CropResource
    {
        Gate::authorize('view', $crop);

        return new CropResource($crop);
    }
}
