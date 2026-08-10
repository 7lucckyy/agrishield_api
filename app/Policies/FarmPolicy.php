<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FarmPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(GlobalRole::PlatformAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Farm $farm): Response
    {
        return $this->canView($user, $farm)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Farm $farm): Response
    {
        if (! $this->canView($user, $farm)) {
            return Response::denyAsNotFound();
        }

        return $farm->owner_user_id === $user->getKey()
            || ($farm->organization_id !== null && $user->hasOrganizationRole(
                $farm->organization_id,
                OrganizationRole::OrganizationAdmin,
            ))
                ? Response::allow()
                : Response::deny('You may view this farm but cannot update it.');
    }

    public function delete(User $user, Farm $farm): Response
    {
        if (! $this->canView($user, $farm)) {
            return Response::denyAsNotFound();
        }

        return $farm->owner_user_id === $user->getKey()
            ? Response::allow()
            : Response::deny('Only the farm owner may delete it.');
    }

    private function canView(User $user, Farm $farm): bool
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
