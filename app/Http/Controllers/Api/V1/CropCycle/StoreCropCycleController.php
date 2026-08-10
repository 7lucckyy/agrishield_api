<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\CropCycle;

use App\Actions\CropCycle\CreateCropCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CropCycle\StoreCropCycleRequest;
use App\Http\Resources\Api\V1\CropCycleResource;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class StoreCropCycleController extends Controller
{
    public function __construct(private CreateCropCycle $createCropCycle) {}

    public function __invoke(StoreCropCycleRequest $request, Farm $farm): JsonResponse
    {
        $response = (new CropCycleResource(
            $this->createCropCycle->execute($farm, $request->validated()),
        ))->additional(['meta' => [
            'message' => 'Crop cycle created. Crop practices will be generated when provider synchronization is available.',
        ]])->response();
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
