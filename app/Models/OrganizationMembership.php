<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationMembershipStatus;
use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property OrganizationRole $role
 * @property OrganizationMembershipStatus $status
 * @property Carbon|null $joined_at
 */
#[Fillable(['role', 'status', 'joined_at'])]
class OrganizationMembership extends Pivot
{
    /**
     * The table's primary key is an auto-incrementing integer.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'organization_user';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'role' => OrganizationRole::class,
            'status' => OrganizationMembershipStatus::class,
        ];
    }
}
