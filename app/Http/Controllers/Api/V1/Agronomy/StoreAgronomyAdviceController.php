<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agronomy;

use App\Actions\Agronomy\CreateAgronomyAdvice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Agronomy\StoreAgronomyAdviceRequest;
use App\Http\Resources\Api\V1\AgronomyAdviceResource;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class StoreAgronomyAdviceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreAgronomyAdviceRequest $request, Farm $farm, CreateAgronomyAdvice $create): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $advice = $create->execute($farm, $user, $request->string('question')->trim()->toString(), $request->string('language')->toString());

        return (new AgronomyAdviceResource($advice))->response()->setStatusCode(201);
    }
}
