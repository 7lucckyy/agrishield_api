<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Actions\Profile\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;

class UpdateProfileController extends Controller
{
    public function __construct(private UpdateUserProfile $updateUserProfile) {}

    public function __invoke(UpdateProfileRequest $request): UserResource
    {
        return new UserResource(
            $this->updateUserProfile->execute($request->user(), $request->validated()),
        );
    }
}
