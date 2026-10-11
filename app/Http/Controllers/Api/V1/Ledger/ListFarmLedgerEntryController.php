<?php

namespace App\Http\Controllers\Api\V1\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ledger\ListFarmLedgerEntryRequest;
use App\Http\Resources\Api\V1\FarmLedgerEntryResource;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListFarmLedgerEntryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ListFarmLedgerEntryRequest $request, Farm $farm): AnonymousResourceCollection
    {
        $entries = $farm->ledgerEntries()
            ->whereBelongsTo($request->user())
            ->when($request->filled('entry_type'), fn (Builder $query): Builder => $query->where('entry_type', $request->string('entry_type')))
            ->when($request->filled('from'), fn (Builder $query): Builder => $query->whereDate('occurred_on', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query): Builder => $query->whereDate('occurred_on', '<=', $request->date('to')))
            ->when($request->filled('farm_crop_cycle_id'), fn (Builder $query): Builder => $query->where('farm_crop_cycle_id', $request->integer('farm_crop_cycle_id')))
            ->latest('occurred_on')
            ->latest('id')
            ->paginate(min(100, max(1, $request->integer('per_page', 30))));

        return FarmLedgerEntryResource::collection($entries);
    }
}
