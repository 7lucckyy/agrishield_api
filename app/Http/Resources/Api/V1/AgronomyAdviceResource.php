<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AgronomyAdvice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class AgronomyAdviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AgronomyAdvice) {
            throw new LogicException('AgronomyAdviceResource requires an AgronomyAdvice.');
        }

        return [
            'id' => $this->resource->uuid,
            'farm_id' => $this->resource->farm->uuid,
            'language' => $this->resource->language,
            'question' => $this->resource->question,
            'answer' => $this->resource->answer,
            'next_steps' => $this->resource->next_steps,
            'uncertainty' => $this->resource->uncertainty,
            'safety_note' => $this->resource->safety_note,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
