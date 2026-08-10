<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListOrganizationMembers
{
    /** @return LengthAwarePaginator<int, User&object{pivot: OrganizationMembership}> */
    public function execute(Organization $organization, int $perPage, string $path): LengthAwarePaginator
    {
        return $organization->users()
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->paginate($perPage)
            ->withPath($path)
            ->withQueryString();
    }
}
