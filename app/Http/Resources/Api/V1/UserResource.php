<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof User) {
            throw new LogicException('UserResource requires a User model.');
        }

        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status->value,
            'locale' => $user->locale,
            'last_login_at' => $user->last_login_at,
            'roles' => $user->getRoleNames()->values(),
            'organizations' => $this->when($user->relationLoaded('organizations'), fn () => $user->organizations
                ->map(fn (Organization $organization): array => [
                    'id' => $organization->getKey(),
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'role' => $organization->membership->role->value,
                    'cluster_name' => $organization->membership->cluster_name,
                    'status' => $organization->membership->status->value,
                ])
                ->values()),
            'created_at' => $user->created_at,
        ];
    }
}
