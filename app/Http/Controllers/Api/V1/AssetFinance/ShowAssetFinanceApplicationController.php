<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetFinanceApplicationResource;
use App\Models\AssetFinanceApplication;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

final class ShowAssetFinanceApplicationController extends Controller
{
    public function __invoke(Organization $organization, AssetFinanceApplication $assetFinanceApplication): AssetFinanceApplicationResource
    {
        abort_unless($assetFinanceApplication->organization_id === $organization->getKey(), 404);
        Gate::authorize('view', $assetFinanceApplication);
        $assetFinanceApplication->load(['farm:id,uuid,name', 'product.financePartner', 'events']);

        return new AssetFinanceApplicationResource($assetFinanceApplication);
    }
}
