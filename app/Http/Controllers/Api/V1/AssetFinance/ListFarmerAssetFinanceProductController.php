<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AssetFinance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FarmerEquipmentResource;
use App\Models\Farm;
use App\Models\User;
use App\Services\Equipment\RecommendEquipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListFarmerAssetFinanceProductController extends Controller
{
    public function __invoke(Request $request, RecommendEquipment $recommendEquipment): AnonymousResourceCollection
    {
        abort_unless(config('equipment.requests_enabled'), 503, 'Equipment requests are not available yet.');
        $request->validate(['farm_id' => ['required', 'uuid']]);
        /** @var User $user */
        $user = $request->user();
        $farm = Farm::query()->whereBelongsTo($user, 'owner')
            ->where('uuid', $request->string('farm_id'))
            ->firstOrFail();

        return FarmerEquipmentResource::collection($recommendEquipment->forFarm($farm));
    }
}
