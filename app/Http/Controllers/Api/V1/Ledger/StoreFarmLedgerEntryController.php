<?php

namespace App\Http\Controllers\Api\V1\Ledger;

use App\Actions\Ledger\CreateFarmLedgerEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ledger\StoreFarmLedgerEntryRequest;
use App\Http\Resources\Api\V1\FarmLedgerEntryResource;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class StoreFarmLedgerEntryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreFarmLedgerEntryRequest $request, Farm $farm, CreateFarmLedgerEntry $create): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $entry = $create->execute($farm, $user, $request->validated());

        return (new FarmLedgerEntryResource($entry))->response()->setStatusCode(201);
    }
}
