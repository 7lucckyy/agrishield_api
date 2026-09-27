<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetFinanceApplicationResource;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ListAssetFinanceApplicationController extends Controller
{
    public function __invoke(Organization $organization): AnonymousResourceCollection
    {
        Gate::authorize('viewAssetFinance', $organization);

        $applications = $organization->assetFinanceApplications()
            ->with(['farm:id,uuid,name', 'product.financePartner'])
            ->latest()
            ->paginate(20);

        return AssetFinanceApplicationResource::collection($applications);
    }
}
