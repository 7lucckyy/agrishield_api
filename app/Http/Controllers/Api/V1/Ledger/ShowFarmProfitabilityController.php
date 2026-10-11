<?php

namespace App\Http\Controllers\Api\V1\Ledger;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\Ledger\CalculateFarmProfitability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowFarmProfitabilityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Farm $farm, CalculateFarmProfitability $calculate): JsonResponse
    {
        abort_unless($farm->owner_user_id === $request->user()?->getKey(), 404);
        $request->validate(['farm_crop_cycle_id' => ['nullable', 'integer']]);

        return response()->json(['data' => $calculate->forFarm($farm, $request->integer('farm_crop_cycle_id') ?: null)]);
    }
}
