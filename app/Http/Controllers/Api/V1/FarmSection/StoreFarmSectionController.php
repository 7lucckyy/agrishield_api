<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\FarmSection;

use App\Actions\FarmSection\CreateFarmSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FarmSection\StoreFarmSectionRequest;
use App\Http\Resources\Api\V1\FarmSectionResource;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class StoreFarmSectionController extends Controller
{
    public function __construct(private CreateFarmSection $createFarmSection) {}

    public function __invoke(StoreFarmSectionRequest $request, Farm $farm): JsonResponse
    {
        $response = (new FarmSectionResource(
            $this->createFarmSection->execute($farm, $request->validated()),
        ))->additional(['meta' => ['message' => 'Farm section created.']])->response();
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
