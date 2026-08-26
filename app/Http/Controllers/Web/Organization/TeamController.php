<?php

namespace App\Http\Controllers\Web\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function __invoke(Organization $organization): View
    {
        Gate::authorize('viewMembers', $organization);

        return view('organization.team', [
            'organization' => $organization,
            'members' => $organization->users()->orderBy('name')->paginate(20),
        ]);
    }
}
