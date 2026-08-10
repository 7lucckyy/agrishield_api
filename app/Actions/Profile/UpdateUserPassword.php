<?php

namespace App\Actions\Profile;

use App\Models\User;

class UpdateUserPassword
{
    public function execute(User $user, string $password): void
    {
        $currentTokenId = $user->currentAccessToken()->getKey();

        $user->update(['password' => $password]);

        $user->tokens()->where('id', '!=', $currentTokenId)->delete();
    }
}
