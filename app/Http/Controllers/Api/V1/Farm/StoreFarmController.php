<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Farm;

use App\Actions\Farm\CreateFarm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Farm\StoreFarmRequest;
use App\Http\Resources\Api\V1\FarmResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class StoreFarmController extends Controller
{
    public function __construct(private CreateFarm $createFarm) {}

    public function __invoke(StoreFarmRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $response = (new FarmResource($this->createFarm->execute(
            $user,
            $request->validated(),
            $request->processedGeometry(),
        )))->additional(['meta' => [
            'message' => 'Farm created. Satellite registration is in progress.',
            'sync_run_id' => null,
        ]])->response();
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
