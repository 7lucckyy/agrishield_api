<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Organization\UpdateOrganizationMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organization\UpdateOrganizationMemberRequest;
use App\Http\Resources\Api\V1\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\User;

final class UpdateOrganizationMemberController extends Controller
{
    public function __construct(private UpdateOrganizationMemberRole $updateOrganizationMemberRole) {}

    public function __invoke(
        UpdateOrganizationMemberRequest $request,
        Organization $organization,
        User $user,
    ): OrganizationMemberResource {
        return new OrganizationMemberResource($this->updateOrganizationMemberRole->execute(
            $organization,
            $user,
            $request->role(),
        ));
    }
}
