<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RevokeCurrentAccessToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __construct(private RevokeCurrentAccessToken $revokeCurrentAccessToken) {}

    public function __invoke(Request $request): Response
    {
        $this->revokeCurrentAccessToken->execute($request->user());

        return response()->noContent();
    }
}
