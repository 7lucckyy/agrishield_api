<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class OrganizationMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof User) {
            throw new LogicException('OrganizationMemberResource requires a User model.');
        }

        $user = $this->resource;

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->membership->role->value,
            'status' => $user->membership->status->value,
            'joined_at' => $user->membership->joined_at?->toISOString(),
        ];
    }
}
