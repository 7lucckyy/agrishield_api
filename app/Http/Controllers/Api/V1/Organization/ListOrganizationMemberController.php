<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Organization\ListOrganizationMembers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organization\ListOrganizationMemberRequest;
use App\Http\Resources\Api\V1\OrganizationMemberCollection;
use App\Models\Organization;

final class ListOrganizationMemberController extends Controller
{
    public function __construct(private ListOrganizationMembers $listOrganizationMembers) {}

    public function __invoke(
        ListOrganizationMemberRequest $request,
        Organization $organization,
    ): OrganizationMemberCollection {
        return new OrganizationMemberCollection($this->listOrganizationMembers->execute(
            $organization,
            $request->perPage(),
            $request->url(),
        ));
    }
}
