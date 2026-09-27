<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Models\AssetFinanceApplication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class AssetFinanceApplicationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(GlobalRole::PlatformAdmin->value) ? true : null;
    }

    public function view(User $user, AssetFinanceApplication $application): Response
    {
        return $user->hasOrganizationRole(
            $application->organization_id,
            [OrganizationRole::OrganizationAdmin, OrganizationRole::Agronomist],
        ) ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, AssetFinanceApplication $application): Response
    {
        return $user->hasOrganizationRole($application->organization_id, OrganizationRole::OrganizationAdmin)
            ? Response::allow()
            : Response::deny('Only an organization administrator can update a finance application.');
    }
}
