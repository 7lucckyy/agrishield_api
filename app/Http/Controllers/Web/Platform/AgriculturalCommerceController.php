<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Platform\StoreAgriculturalOfferRequest;
use App\Http\Requests\Web\Platform\StoreAgriculturalPartnerRequest;
use App\Http\Requests\Web\Platform\UpdateAgriculturalServiceRequestRequest;
use App\Models\AgriculturalOffer;
use App\Models\AgriculturalPartner;
use App\Models\AgriculturalServiceRequest;
use App\Models\Crop;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AgriculturalCommerceController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $partners = AgriculturalPartner::query()->latest()->get();
        $offers = AgriculturalOffer::query()->with(['partner:id,name', 'crop:id,name'])->latest()->get();
        $serviceRequests = AgriculturalServiceRequest::query()
            ->with(['user:id,name,phone', 'farm:id,uuid,name,state', 'offer:id,agricultural_partner_id,title', 'offer.partner:id,name'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();
        $crops = Crop::query()->where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('platform.agricultural-commerce', compact('partners', 'offers', 'serviceRequests', 'crops', 'status'));
    }

    public function storePartner(StoreAgriculturalPartnerRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $partner = new AgriculturalPartner($request->safe()->except('verification_note'));
        $partner->verified_at = now();
        $partner->verifiedBy()->associate($user);
        $partner->metadata = ['verification_note' => $request->string('verification_note')->toString()];
        $partner->save();

        return to_route('platform.commerce.index')->with('status', 'Verified partner added.');
    }

    public function storeOffer(StoreAgriculturalOfferRequest $request): RedirectResponse
    {
        $offer = new AgriculturalOffer($request->safe()->except(['requirements_text', 'agricultural_partner_id', 'crop_id']));
        $offer->requirements = collect(preg_split('/\r\n|\r|\n/', $request->string('requirements_text')->toString()))
            ->map(static fn (string $requirement): string => trim($requirement))
            ->filter()
            ->values()
            ->all();
        $offer->fulfillment_channels = ['partner_confirmation'];
        $offer->partner()->associate(AgriculturalPartner::query()->findOrFail($request->integer('agricultural_partner_id')));
        if ($request->filled('crop_id')) {
            $offer->crop()->associate(Crop::query()->findOrFail($request->integer('crop_id')));
        }
        $offer->save();

        return to_route('platform.commerce.index')->with('status', 'Farmer offer published.');
    }

    public function updateRequest(UpdateAgriculturalServiceRequestRequest $request, AgriculturalServiceRequest $agriculturalServiceRequest): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        DB::transaction(function () use ($request, $agriculturalServiceRequest, $user): void {
            $history = $agriculturalServiceRequest->metadata['status_history'] ?? [];
            $history[] = ['status' => $request->string('status')->toString(), 'note' => $request->string('decision_note')->toString(), 'user_id' => $user->getKey(), 'recorded_at' => now()->toISOString()];
            $agriculturalServiceRequest->forceFill(['status' => $request->string('status')->toString(), 'decision_note' => $request->string('decision_note')->toString(), 'metadata' => array_merge($agriculturalServiceRequest->metadata ?? [], ['status_history' => $history])])->save();
        });

        return to_route('platform.commerce.index')->with('status', 'Farmer request updated.');
    }
}
