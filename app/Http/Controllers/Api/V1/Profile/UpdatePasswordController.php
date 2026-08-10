<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Actions\Profile\UpdateUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use Illuminate\Http\Response;

class UpdatePasswordController extends Controller
{
    public function __construct(private UpdateUserPassword $updateUserPassword) {}

    public function __invoke(UpdatePasswordRequest $request): Response
    {
        $this->updateUserPassword->execute(
            $request->user(),
            $request->validated('password'),
        );

        return response()->noContent();
    }
}
