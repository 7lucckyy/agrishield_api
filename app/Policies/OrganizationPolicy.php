<?php

namespace App\Policies;

use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class OrganizationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(GlobalRole::PlatformAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Organization $organization): Response
    {
        return $this->authorizeMember($user, $organization, [
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::Agronomist,
        ]);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Organization $organization): Response
    {
        return $this->authorizeMember(
            $user,
            $organization,
            [OrganizationRole::OrganizationAdmin],
            requiresActiveOrganization: true,
        );
    }

    public function changeStatus(User $user, Organization $organization): bool
    {
        return false;
    }

    public function rotateReferralCode(User $user, Organization $organization): Response
    {
        return $this->authorizeMember(
            $user,
            $organization,
            [OrganizationRole::OrganizationAdmin],
            requiresActiveOrganization: true,
        );
    }

    public function viewReferralCode(User $user, Organization $organization): Response
    {
        return $this->authorizeMember($user, $organization, [OrganizationRole::OrganizationAdmin]);
    }

    public function viewMembers(User $user, Organization $organization): Response
    {
        return $this->authorizeMember($user, $organization, [
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::Agronomist,
        ]);
    }

    public function updateMemberRole(User $user, Organization $organization): Response
    {
        return $this->authorizeMember(
            $user,
            $organization,
            [OrganizationRole::OrganizationAdmin],
            requiresActiveOrganization: true,
        );
    }

    public function removeMember(User $user, Organization $organization): Response
    {
        return $this->authorizeMember(
            $user,
            $organization,
            [OrganizationRole::OrganizationAdmin],
            requiresActiveOrganization: true,
        );
    }

    public function viewFarms(User $user, Organization $organization): Response
    {
        return $this->authorizeMember($user, $organization, [
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::Agronomist,
        ]);
    }

    public function viewOverview(User $user, Organization $organization): Response
    {
        return $this->authorizeMember($user, $organization, [
            OrganizationRole::OrganizationAdmin,
            OrganizationRole::Agronomist,
        ]);
    }

    /** @param array<array-key, OrganizationRole> $roles */
    private function authorizeMember(
        User $user,
        Organization $organization,
        array $roles,
        bool $requiresActiveOrganization = false,
    ): Response {
        if (! $user->belongsToOrganization($organization)) {
            return Response::denyAsNotFound();
        }

        if ($organization->status === OrganizationStatus::Suspended) {
            return Response::deny('This organization is suspended.');
        }

        if (! $user->hasOrganizationRole($organization, $roles)) {
            return Response::deny('You do not have the required organization role.');
        }

        if ($requiresActiveOrganization && $organization->status !== OrganizationStatus::Active) {
            return Response::deny('This organization is read-only.');
        }

        return Response::allow();
    }
}
