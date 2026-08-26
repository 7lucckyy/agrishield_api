<?php

namespace App\Http\Controllers\Web\Organization;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FarmController extends Controller
{
    public function index(Request $request, Organization $organization): View
    {
        Gate::authorize('viewFarms', $organization);
        $search = trim($request->string('search')->toString());
        $farms = $organization->farms()
            ->with(['owner:id,name', 'activeCropCycle.crop:id,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('organization.farms.index', compact('organization', 'farms', 'search'));
    }

    public function show(Organization $organization, Farm $farm): View
    {
        Gate::authorize('viewFarms', $organization);
        abort_unless($farm->organization_id === $organization->getKey(), 404);
        $farm->load(['owner:id,name,email', 'activeCropCycle.crop:id,name', 'satelliteObservations' => fn ($query) => $query->latest('observed_at')->limit(8), 'weatherForecasts' => fn ($query) => $query->orderBy('forecast_date')->limit(7)]);

        return view('organization.farms.show', compact('organization', 'farm'));
    }
}
