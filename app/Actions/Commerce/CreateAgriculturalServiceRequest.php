<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Enums\AgriculturalServiceRequestStatus;
use App\Models\AgriculturalOffer;
use App\Models\AgriculturalServiceRequest;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateAgriculturalServiceRequest
{
    /** @param array<string, mixed> $data */
    public function execute(User $user, Farm $farm, AgriculturalOffer $offer, array $data): AgriculturalServiceRequest
    {
        if ($farm->owner_user_id !== $user->getKey()) {
            throw ValidationException::withMessages(['farm_id' => 'Select a farm you own.']);
        }

        $cropCycleId = $data['farm_crop_cycle_id'] ?? null;
        if ($cropCycleId !== null && ! CropCycle::query()->whereKey($cropCycleId)->whereBelongsTo($farm)->exists()) {
            throw ValidationException::withMessages(['farm_crop_cycle_id' => 'Select a crop season attached to this farm.']);
        }

        return DB::transaction(function () use ($user, $farm, $offer, $data, $cropCycleId): AgriculturalServiceRequest {
            $lockedOffer = AgriculturalOffer::query()->available()->lockForUpdate()->find($offer->getKey());
            if ($lockedOffer === null) {
                throw ValidationException::withMessages(['offer_id' => 'This verified offer is no longer available.']);
            }

            $quantity = $data['quantity'] ?? null;
            if (($lockedOffer->minimum_quantity !== null || $lockedOffer->available_quantity !== null) && $quantity === null) {
                throw ValidationException::withMessages(['quantity' => 'Enter the quantity you need or can supply.']);
            }
            if ($lockedOffer->minimum_quantity !== null && (float) $quantity < (float) $lockedOffer->minimum_quantity) {
                throw ValidationException::withMessages(['quantity' => 'The requested quantity is below this offer minimum.']);
            }
            if ($lockedOffer->available_quantity !== null) {
                $committedQuantity = (float) $lockedOffer->serviceRequests()
                    ->whereNotIn('status', [AgriculturalServiceRequestStatus::Declined, AgriculturalServiceRequestStatus::Cancelled])
                    ->sum('quantity');
                if ($committedQuantity + (float) $quantity > (float) $lockedOffer->available_quantity) {
                    $remainingQuantity = max(0, (float) $lockedOffer->available_quantity - $committedQuantity);
                    throw ValidationException::withMessages(['quantity' => "Only {$remainingQuantity} {$lockedOffer->unit} remain for this offer."]);
                }
            }

            $serviceRequest = new AgriculturalServiceRequest([
                'quantity' => $quantity,
                'unit' => $lockedOffer->unit,
                'budget_amount' => $data['budget_amount'] ?? null,
                'requested_for' => $data['requested_for'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'metadata' => ['offer_snapshot' => $lockedOffer->only(['title', 'unit_price', 'currency', 'unit'])],
            ]);
            $serviceRequest->request_type = $lockedOffer->category;
            $serviceRequest->status = AgriculturalServiceRequestStatus::Submitted;
            $serviceRequest->consented_at = now();
            $serviceRequest->submitted_at = now();
            $serviceRequest->offer()->associate($lockedOffer);
            $serviceRequest->user()->associate($user);
            $serviceRequest->farm()->associate($farm);
            $serviceRequest->organization()->associate($farm->organization);
            if ($cropCycleId !== null) {
                $serviceRequest->farm_crop_cycle_id = $cropCycleId;
            }
            $serviceRequest->save();

            return $serviceRequest;
        });
    }
}
