<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\AuthenticationResource;

class LoginController extends Controller
{
    public function __construct(private AuthenticateUser $authenticateUser) {}

    public function __invoke(LoginRequest $request): AuthenticationResource
    {
        return (new AuthenticationResource(
            $this->authenticateUser->execute($request->validated()),
        ))->additional(['meta' => []]);
    }
}
