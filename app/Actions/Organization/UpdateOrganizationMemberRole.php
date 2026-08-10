<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Exceptions\LastOrganizationAdminRequiredException;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateOrganizationMemberRole
{
    public function execute(Organization $organization, User $member, OrganizationRole $role): User
    {
        DB::transaction(function () use ($organization, $member, $role): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->getKey())
                ->where('user_id', $member->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($membership->status === OrganizationMembershipStatus::Active
                && $membership->role === OrganizationRole::OrganizationAdmin
                && $role !== OrganizationRole::OrganizationAdmin) {
                $this->ensureAnotherActiveAdministrator($organization, $member);
            }

            $membership->role = $role;
            $membership->save();
        });

        return $organization->users()->whereKey($member->getKey())->firstOrFail();
    }

    private function ensureAnotherActiveAdministrator(Organization $organization, User $member): void
    {
        $hasAnotherAdministrator = OrganizationMembership::query()
            ->where('organization_id', $organization->getKey())
            ->where('user_id', '!=', $member->getKey())
            ->where('status', OrganizationMembershipStatus::Active->value)
            ->where('role', OrganizationRole::OrganizationAdmin->value)
            ->lockForUpdate()
            ->first(['id']) !== null;

        if (! $hasAnotherAdministrator) {
            throw new LastOrganizationAdminRequiredException;
        }
    }
}
