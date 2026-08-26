<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use App\Exceptions\LastOrganizationAdminRequiredException;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateOrganizationMemberRole
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function execute(Organization $organization, User $member, OrganizationRole $role): User
    {
        $beforeRole = null;
        DB::transaction(function () use ($organization, $member, $role, &$beforeRole): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->getKey())
                ->where('user_id', $member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $beforeRole = $membership->role->value;

            if ($membership->status === OrganizationMembershipStatus::Active
                && $membership->role === OrganizationRole::OrganizationAdmin
                && $role !== OrganizationRole::OrganizationAdmin) {
                $this->ensureAnotherActiveAdministrator($organization, $member);
            }

            $membership->role = $role;
            $membership->save();
        });
        $this->recordAuditLog->execute('member.role_changed', $organization, [
            'member_id' => $member->getKey(),
            'before' => ['role' => $beforeRole],
            'after' => ['role' => $role->value],
        ]);

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
