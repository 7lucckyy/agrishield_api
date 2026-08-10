<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Crop;

use App\Actions\Crop\CreateCrop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Crop\StoreCropRequest;
use App\Http\Resources\Api\V1\CropResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class StoreCropController extends Controller
{
    public function __construct(private CreateCrop $createCrop) {}

    public function __invoke(StoreCropRequest $request): JsonResponse
    {
        $response = (new CropResource(
            $this->createCrop->execute($request->validated()),
        ))->response();
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
