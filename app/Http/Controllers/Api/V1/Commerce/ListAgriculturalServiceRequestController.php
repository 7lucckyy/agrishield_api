<?php

namespace App\Http\Controllers\Api\V1\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AgriculturalServiceRequestResource;
use App\Models\AgriculturalServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListAgriculturalServiceRequestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        return AgriculturalServiceRequestResource::collection(
            AgriculturalServiceRequest::query()
                ->whereBelongsTo($request->user())
                ->with(['farm:id,uuid,name', 'offer:id,agricultural_partner_id,title', 'offer.partner:id,name'])
                ->latest()
                ->paginate(min(50, max(1, $request->integer('per_page', 20))))
        );
    }
}
