<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\GlobalRole;
use App\Models\Crop;
use App\Models\User;

final class CropPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(GlobalRole::PlatformAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Crop $crop): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Crop $crop): bool
    {
        return false;
    }

    public function delete(User $user, Crop $crop): bool
    {
        return false;
    }
}
