<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Actions\Profile\GetUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

class ShowProfileController extends Controller
{
    public function __construct(private GetUserProfile $getUserProfile) {}

    public function __invoke(Request $request): UserResource
    {
        return new UserResource($this->getUserProfile->execute($request->user()));
    }
}
