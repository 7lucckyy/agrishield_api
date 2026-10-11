<?php

namespace App\Http\Controllers\Api\V1\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Commerce\ListAgriculturalOfferRequest;
use App\Http\Resources\Api\V1\AgriculturalOfferResource;
use App\Models\AgriculturalOffer;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListAgriculturalOfferController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ListAgriculturalOfferRequest $request): AnonymousResourceCollection
    {
        $state = $request->validated('state');
        if ($request->filled('farm_id')) {
            $farm = Farm::query()->whereBelongsTo($request->user(), 'owner')->where('uuid', $request->string('farm_id'))->firstOrFail();
            $state ??= $farm->state;
        }

        $offers = AgriculturalOffer::query()
            ->available()
            ->with(['partner:id,name,type,contact_phone,verified_at', 'crop:id,name'])
            ->withSum([
                'serviceRequests as committed_quantity' => fn (Builder $query): Builder => $query->whereNotIn('status', ['declined', 'cancelled']),
            ], 'quantity')
            ->when($request->filled('category'), fn (Builder $query): Builder => $query->where('category', $request->string('category')))
            ->when($request->filled('crop_id'), fn (Builder $query): Builder => $query->where('crop_id', $request->integer('crop_id')))
            ->when($state !== null, fn (Builder $query): Builder => $query->where(fn (Builder $location): Builder => $location->whereNull('state')->orWhere('state', $state)))
            ->latest()
            ->paginate(min(50, max(1, $request->integer('per_page', 20))));

        return AgriculturalOfferResource::collection($offers);
    }
}
