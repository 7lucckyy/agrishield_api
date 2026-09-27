<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetFinanceProductResource;
use App\Models\AssetFinanceProduct;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ListAssetFinanceProductController extends Controller
{
    public function __invoke(Organization $organization): AnonymousResourceCollection
    {
        Gate::authorize('viewAssetFinance', $organization);

        $products = AssetFinanceProduct::query()
            ->where('is_active', true)
            ->whereHas('financePartner', fn ($query) => $query->where('is_active', true))
            ->with('financePartner')
            ->orderBy('name')
            ->get();

        return AssetFinanceProductResource::collection($products);
    }
}
