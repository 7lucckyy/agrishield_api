<?php

namespace App\Actions\Auth;

use App\Models\User;

class RevokeAllAccessTokens
{
    public function execute(User $user): void
    {
        $user->tokens()->delete();
    }
}
