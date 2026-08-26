<?php

namespace App\Http\Resources\Api\V1;

use App\Models\IntegrationAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class IntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof IntegrationAccount) {
            throw new LogicException('IntegrationResource requires an IntegrationAccount model.');
        }

        $integration = $this->resource;

        return [
            'id' => $integration->getKey(),
            'provider' => $integration->provider,
            'label' => $integration->label,
            'status' => $integration->status->value,
            'circuit_state' => $integration->circuit_state->value,
            'last_success_at' => $integration->last_success_at?->toISOString(),
            'last_failure_at' => $integration->last_failure_at?->toISOString(),
            'consecutive_failures' => $integration->consecutive_failures,
        ];
    }
}
