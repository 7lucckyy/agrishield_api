<?php

namespace App\Http\Controllers\Web\Organization;

use App\Actions\Diagnosis\CreateDiagnosisRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Organization\StoreDiagnosisRequest;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        $farm->load([
            'owner:id,name,email',
            'activeCropCycle.crop:id,name',
            'satelliteObservations' => fn ($query) => $query->latest('captured_at')->limit(8),
            'weatherForecasts' => fn ($query) => $query->orderBy('forecast_date')->limit(7),
            'diagnosisRequests' => fn ($query) => $query->with(['requestedBy:id,name', 'cropCycle.crop:id,name'])->latest()->limit(8),
        ]);

        return view('organization.farms.show', compact('organization', 'farm'));
    }

    public function storeDiagnosis(StoreDiagnosisRequest $request, Organization $organization, Farm $farm, CreateDiagnosisRequest $create): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $image */
        $image = $request->file('image');
        $create->execute($farm, $user, $image, $request->safe()->except('image'));

        return to_route('organization.farms.show', [$organization, $farm])
            ->with('status', 'Crop photo received. The screening result will appear here when processing is complete.');
    }
}
