<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SyncRunResource;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ListFarmSyncRunController extends Controller
{
    public function __invoke(Request $request, Farm $farm): AnonymousResourceCollection
    {
        Gate::authorize('update', $farm);
        $runs = $farm->syncRuns()->latest()->paginate(min(100, max(1, $request->integer('per_page', 25))));

        return SyncRunResource::collection($runs);
    }
}
