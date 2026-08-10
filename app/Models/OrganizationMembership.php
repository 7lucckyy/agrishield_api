<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property string $role
 * @property string $status
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }
}
