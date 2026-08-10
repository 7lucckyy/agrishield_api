<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Organization\ListOrganizationFarms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organization\ListOrganizationFarmRequest;
use App\Http\Resources\Api\V1\FarmCollection;
use App\Models\Organization;
use App\Models\User;

final class ListOrganizationFarmController extends Controller
{
    public function __construct(private ListOrganizationFarms $listOrganizationFarms) {}

    public function __invoke(ListOrganizationFarmRequest $request, Organization $organization): FarmCollection
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->listOrganizationFarms->execute(
            $user,
            $organization,
            $request->filters(),
            $request->sort(),
            $request->perPage(),
            $request->url(),
        );

        return (new FarmCollection($result->farms))->withTotals($result->totals);
    }
}
