<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Actions\AssetFinance\UpdateAssetFinanceApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssetFinance\UpdateAssetFinanceApplicationRequest;
use App\Http\Resources\Api\V1\AssetFinanceApplicationResource;
use App\Models\AssetFinanceApplication;
use App\Models\Organization;
use App\Models\User;

final class UpdateAssetFinanceApplicationController extends Controller
{
    public function __invoke(UpdateAssetFinanceApplicationRequest $request, Organization $organization, AssetFinanceApplication $assetFinanceApplication, UpdateAssetFinanceApplication $update): AssetFinanceApplicationResource
    {
        abort_unless($assetFinanceApplication->organization_id === $organization->getKey(), 404);
        /** @var User $user */
        $user = $request->user();
        $application = $update->execute($assetFinanceApplication, $user, $request->validated());
        $application->load(['farm:id,uuid,name', 'product.financePartner', 'events']);

        return new AssetFinanceApplicationResource($application);
    }
}
