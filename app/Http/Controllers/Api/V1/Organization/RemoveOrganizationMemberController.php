<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Organization\RemoveOrganizationMember;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class RemoveOrganizationMemberController extends Controller
{
    public function __construct(private RemoveOrganizationMember $removeOrganizationMember) {}

    public function __invoke(Organization $organization, User $user): Response
    {
        Gate::authorize('removeMember', $organization);
        $this->removeOrganizationMember->execute($organization, $user);

        return response()->noContent();
    }
}
