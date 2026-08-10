<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class CropCyclePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(GlobalRole::PlatformAdmin->value) ? true : null;
    }

    public function view(User $user, CropCycle $cropCycle): Response
    {
        return $this->canManage($user, $cropCycle->farm)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user, Farm $farm): Response
    {
        return $this->canManage($user, $farm)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, CropCycle $cropCycle): Response
    {
        return $this->view($user, $cropCycle);
    }

    public function delete(User $user, CropCycle $cropCycle): Response
    {
        if (! $this->canManage($user, $cropCycle->farm)) {
            return Response::denyAsNotFound();
        }

        if ($cropCycle->farm->owner_user_id !== $user->getKey()) {
            return Response::deny('Only the farm owner may delete a crop cycle.');
        }

        return Response::allow();
    }

    private function canManage(User $user, Farm $farm): bool
    {
        if ($farm->owner_user_id === $user->getKey()) {
            return true;
        }

        return $farm->organization_id !== null && $user->hasOrganizationRole(
            $farm->organization_id,
            [OrganizationRole::OrganizationAdmin, OrganizationRole::Agronomist],
        );
    }
}
