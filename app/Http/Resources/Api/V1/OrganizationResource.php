<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Organization) {
            throw new LogicException('OrganizationResource requires an Organization model.');
        }

        $organization = $this->resource;

        return [
            'id' => $organization->getKey(),
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status->value,
            'contact_email' => $organization->contact_email,
            'contact_phone' => $organization->contact_phone,
            'country' => $organization->country,
            'referral_code' => $this->when($request->user()?->can('viewReferralCode', $organization) === true, $organization->referral_code),
            'referral_code_expires_at' => $this->when($request->user()?->can('viewReferralCode', $organization) === true, $organization->referral_code_expires_at?->toISOString()),
            'created_at' => $organization->created_at?->toISOString(),
        ];
    }
}
