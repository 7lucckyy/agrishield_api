<?php

namespace App\Http\Controllers\Web\Organization;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class AdvisoryController extends Controller
{
    public function __invoke(Organization $organization): View
    {
        Gate::authorize('viewOverview', $organization);

        $advisories = Advisory::query()
            ->whereHas('farm', fn (Builder $query): Builder => $query->whereBelongsTo($organization))
            ->with(['farm:id,uuid,name'])
            ->withCount('acknowledgements')
            ->latest('observed_at')
            ->paginate(20);

        return view('organization.advisories', compact('organization', 'advisories'));
    }
}
