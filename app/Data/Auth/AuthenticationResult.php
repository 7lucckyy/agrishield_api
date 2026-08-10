<?php

namespace App\Data\Auth;

use App\Models\User;

final readonly class AuthenticationResult
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
    ) {}
}
