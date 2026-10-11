<?php

namespace App\Http\Controllers\Api\V1\Commerce;

use App\Actions\Commerce\CreateAgriculturalServiceRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Commerce\StoreAgriculturalServiceRequestRequest;
use App\Http\Resources\Api\V1\AgriculturalServiceRequestResource;
use App\Models\AgriculturalOffer;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class StoreAgriculturalServiceRequestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreAgriculturalServiceRequestRequest $request, CreateAgriculturalServiceRequest $create): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $farm = Farm::query()->whereBelongsTo($user, 'owner')->where('uuid', $request->string('farm_id'))->firstOrFail();
        $offer = AgriculturalOffer::query()->findOrFail($request->integer('offer_id'));
        $serviceRequest = $create->execute($user, $farm, $offer, $request->validated());
        $serviceRequest->load(['farm:id,uuid,name', 'offer.partner:id,name']);

        return (new AgriculturalServiceRequestResource($serviceRequest))->response()->setStatusCode(201);
    }
}
