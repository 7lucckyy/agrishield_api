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

    public function execute(Organization $organization, User $member, OrganizationRole $role, ?string $clusterName = null): User
    {
        $beforeRole = null;
        $beforeCluster = null;
        DB::transaction(function () use ($organization, $member, $role, $clusterName, &$beforeRole, &$beforeCluster): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->getKey())
                ->where('user_id', $member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $beforeRole = $membership->role->value;
            $beforeCluster = $membership->cluster_name;

            if ($membership->status === OrganizationMembershipStatus::Active
                && $membership->role === OrganizationRole::OrganizationAdmin
                && $role !== OrganizationRole::OrganizationAdmin) {
                $this->ensureAnotherActiveAdministrator($organization, $member);
            }

            $membership->role = $role;
            $membership->cluster_name = $clusterName;
            $membership->save();
        });
        $this->recordAuditLog->execute('member.role_changed', $organization, [
            'member_id' => $member->getKey(),
            'before' => ['role' => $beforeRole, 'cluster_name' => $beforeCluster],
            'after' => ['role' => $role->value, 'cluster_name' => $clusterName],
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
