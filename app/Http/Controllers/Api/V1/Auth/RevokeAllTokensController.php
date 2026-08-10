<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RevokeAllAccessTokens;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RevokeAllTokensController extends Controller
{
    public function __construct(private RevokeAllAccessTokens $revokeAllAccessTokens) {}

    public function __invoke(Request $request): Response
    {
        $this->revokeAllAccessTokens->execute($request->user());

        return response()->noContent();
    }
}
