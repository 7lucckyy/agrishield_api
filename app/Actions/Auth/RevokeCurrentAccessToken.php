<?php

namespace App\Actions\Auth;

use App\Models\User;

class RevokeCurrentAccessToken
{
    public function execute(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
