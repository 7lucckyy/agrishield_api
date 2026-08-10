<?php

declare(strict_types=1);

namespace App\Actions\Organization;

use App\Actions\Farm\ListFarms;
use App\Data\Farm\FarmListResult;
use App\Models\Organization;
use App\Models\User;

final readonly class ListOrganizationFarms
{
    public function __construct(private ListFarms $listFarms) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  array{field: string, direction: 'asc'|'desc'}  $sort
     */
    public function execute(
        User $user,
        Organization $organization,
        array $filters,
        array $sort,
        int $perPage,
        string $path,
    ): FarmListResult {
        $filters['organization_id'] = $organization->getKey();

        return $this->listFarms->execute($user, $filters, $sort, $perPage, $path);
    }
}
