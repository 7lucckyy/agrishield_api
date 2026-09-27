<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AssetFinanceApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AssetFinanceApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AssetFinanceApplication) {
            throw new LogicException('AssetFinanceApplicationResource requires an AssetFinanceApplication model.');
        }

        $application = $this->resource;

        return [
            'id' => $application->uuid,
            'organization_id' => $application->organization_id,
            'farm' => ['id' => $application->farm->uuid, 'name' => $application->farm->name],
            'crop_cycle_id' => $application->farm_crop_cycle_id,
            'product' => new AssetFinanceProductResource($application->product),
            'status' => $application->status->value,
            'quantity' => $application->quantity,
            'requested_amount' => (float) $application->requested_amount,
            'purpose' => $application->purpose,
            'consent' => [
                'channel' => $application->consent_channel,
                'version' => $application->consent_version,
                'recorded_at' => $application->consented_at->toISOString(),
            ],
            'partner_reference' => $application->partner_reference,
            'decision_note' => $application->decision_note,
            'delivery_verified_at' => $application->delivery_verified_at?->toISOString(),
            'repayment' => [
                'status' => $application->repayment_status->value,
                'outstanding_amount' => $application->outstanding_amount === null ? null : (float) $application->outstanding_amount,
                'next_payment_due_at' => $application->next_payment_due_at?->toDateString(),
                'last_partner_sync_at' => $application->last_partner_sync_at?->toISOString(),
            ],
            'events' => $this->whenLoaded('events', fn () => $application->events->map(fn ($event): array => [
                'type' => $event->event_type,
                'status' => $event->status?->value,
                'note' => $event->note,
                'created_at' => $event->created_at?->toISOString(),
            ])),
            'submitted_at' => $application->submitted_at->toISOString(),
            'created_at' => $application->created_at?->toISOString(),
        ];
    }
}
