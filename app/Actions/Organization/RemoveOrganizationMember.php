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

final class RemoveOrganizationMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function execute(Organization $organization, User $member): void
    {
        DB::transaction(function () use ($organization, $member): void {
            $membership = OrganizationMembership::query()
                ->where('organization_id', $organization->getKey())
                ->where('user_id', $member->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($membership->status === OrganizationMembershipStatus::Active
                && $membership->role === OrganizationRole::OrganizationAdmin) {
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

            $membership->status = OrganizationMembershipStatus::Removed;
            $membership->save();
        });
        $this->recordAuditLog->execute('member.removed', $organization, [
            'member_id' => $member->getKey(),
            'after' => ['status' => OrganizationMembershipStatus::Removed->value],
        ]);
    }
}
