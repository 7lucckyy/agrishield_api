<?php

namespace App\Actions\Auth;

use App\Data\Auth\AuthenticationResult;
use App\Enums\UserStatus;
use App\Exceptions\Auth\InactiveAccountException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser
{
    /** @param array<string, mixed> $credentials */
    public function execute(array $credentials): AuthenticationResult
    {
        $user = User::query()
            ->when(
                Arr::get($credentials, 'email') !== null,
                fn ($query) => $query->where('email', Arr::get($credentials, 'email')),
                fn ($query) => $query->where('phone', Arr::get($credentials, 'phone')),
            )
            ->first();

        if ($user === null || ! Hash::check((string) Arr::get($credentials, 'password'), $user->password)) {
            throw new InvalidCredentialsException;
        }

        if ($user->status !== UserStatus::Active) {
            $user->tokens()->delete();

            throw new InactiveAccountException;
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $user->load('organizations');
        $token = $user->createToken(
            (string) Arr::get($credentials, 'device_name', 'mobile'),
            ['*'],
            now()->addDays(90),
        );

        return new AuthenticationResult($user, $token->plainTextToken);
    }
}
