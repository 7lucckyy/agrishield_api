<?php

namespace App\Actions\Profile;

use App\Models\User;

class UpdateUserProfile
{
    /** @param array<string, mixed> $data */
    public function execute(User $user, array $data): User
    {
        $user->update($data);

        return $user->load('organizations');
    }
}
