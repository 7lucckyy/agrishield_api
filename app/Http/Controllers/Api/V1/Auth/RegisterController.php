<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\AuthenticationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegisterController extends Controller
{
    public function __construct(private RegisterUser $registerUser) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $authentication = $this->registerUser->execute(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        $response = (new AuthenticationResource($authentication))
            ->additional(['meta' => ['referral_applied' => $request->filled('referral_code')]])
            ->response();
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
