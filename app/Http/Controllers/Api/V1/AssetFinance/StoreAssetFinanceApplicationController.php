<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Actions\AssetFinance\CreateAssetFinanceApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssetFinance\StoreAssetFinanceApplicationRequest;
use App\Http\Resources\Api\V1\AssetFinanceApplicationResource;
use App\Models\AssetFinanceProduct;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class StoreAssetFinanceApplicationController extends Controller
{
    public function __invoke(StoreAssetFinanceApplicationRequest $request, Organization $organization, CreateAssetFinanceApplication $create): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $farm = Farm::query()->whereBelongsTo($organization)->where('uuid', $request->string('farm_id'))->firstOrFail();
        $product = AssetFinanceProduct::query()->findOrFail($request->integer('asset_finance_product_id'));
        $application = $create->execute($organization, $farm, $user, $product, $request->validated());
        $application->load(['farm:id,uuid,name', 'product.financePartner', 'events']);

        return (new AssetFinanceApplicationResource($application))->response()->setStatusCode(201);
    }
}
