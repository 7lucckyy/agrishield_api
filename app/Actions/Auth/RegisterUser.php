<?php

namespace App\Actions\Auth;

use App\Data\Auth\AuthenticationResult;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /** @param array<string, mixed> $data */
    public function execute(array $data): AuthenticationResult
    {
        return DB::transaction(function () use ($data): AuthenticationResult {
            $user = User::query()->create(Arr::only($data, [
                'name',
                'phone',
                'password',
                'locale',
            ]));

            $user->load('organizations');
            $token = $user->createToken('mobile', ['*']);

            return new AuthenticationResult($user, $token->plainTextToken);
        });
    }
}
