<?php

namespace App\Http\Controllers\Api\V1\Alert;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AlertDeliveryResource;
use App\Models\AlertDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListAlertDeliveryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        return AlertDeliveryResource::collection(
            AlertDelivery::query()
                ->whereBelongsTo($request->user())
                ->with('advisory:id,title,summary,severity')
                ->latest()
                ->paginate(min(50, max(1, $request->integer('per_page', 20))))
        );
    }
}
