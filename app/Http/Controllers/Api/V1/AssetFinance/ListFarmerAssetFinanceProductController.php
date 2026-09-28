<?php

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetFinanceProductResource;
use App\Models\AssetFinanceProduct;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListFarmerAssetFinanceProductController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $products = AssetFinanceProduct::query()
            ->where('is_active', true)
            ->whereHas('financePartner', fn ($query) => $query->where('is_active', true))
            ->with('financePartner')
            ->orderBy('name')
            ->get();

        return AssetFinanceProductResource::collection($products);
    }
}
