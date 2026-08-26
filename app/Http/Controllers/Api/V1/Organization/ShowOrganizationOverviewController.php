<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Organization\GetOrganizationOverview;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowOrganizationOverviewController extends Controller
{
    public function __invoke(Request $request, Organization $organization, GetOrganizationOverview $overview): JsonResponse
    {
        Gate::authorize('viewOverview', $organization);

        return response()->json([
            'data' => $overview->execute($organization),
            'meta' => ['generated_at' => now()->toISOString(), 'cache_ttl_seconds' => 600],
        ]);
    }
}
