<?php

namespace App\Http\Controllers\Web\Platform;

use App\Actions\Organization\GetOrganizationOverview;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $organizations = Organization::query()
            ->withCount(['farms', 'users'])
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('contact_email', 'ilike', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('platform.organizations.index', compact('organizations', 'search'));
    }

    public function show(Organization $organization, GetOrganizationOverview $overview): View
    {
        $organization->loadCount(['users', 'farms']);

        return view('platform.organizations.show', [
            'organization' => $organization,
            'overview' => $overview->execute($organization),
            'farms' => $organization->farms()->with('owner:id,name')->latest()->limit(8)->get(),
        ]);
    }
}
