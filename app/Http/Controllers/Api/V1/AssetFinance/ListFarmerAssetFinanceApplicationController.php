<?php

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetFinanceApplicationResource;
use App\Models\AssetFinanceApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListFarmerAssetFinanceApplicationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $applications = AssetFinanceApplication::query()
            ->whereBelongsTo($user, 'applicant')
            ->with(['farm:id,uuid,name', 'product.financePartner'])
            ->latest()
            ->paginate(20);

        return AssetFinanceApplicationResource::collection($applications);
    }
}
