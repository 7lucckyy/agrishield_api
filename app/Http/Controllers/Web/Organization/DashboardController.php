<?php

namespace App\Http\Controllers\Web\Organization;

use App\Actions\Organization\GetOrganizationOverview;
use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Organization $organization, GetOrganizationOverview $overview): View
    {
        Gate::authorize('viewOverview', $organization);

        return view('organization.dashboard', [
            'organization' => $organization,
            'overview' => $overview->execute($organization),
            'farms' => $organization->farms()->with('activeCropCycle.crop:id,name')->latest()->limit(5)->get(),
            'advisories' => Advisory::query()
                ->whereHas('farm', fn (Builder $query): Builder => $query->whereBelongsTo($organization))
                ->with('farm:id,uuid,name')
                ->latest('observed_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
