<?php

namespace App\Actions\Profile;

use App\Models\User;

class GetUserProfile
{
    public function execute(User $user): User
    {
        return $user->load('organizations');
    }
}
