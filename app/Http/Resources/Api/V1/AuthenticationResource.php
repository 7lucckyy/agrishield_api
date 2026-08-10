<?php

namespace App\Http\Resources\Api\V1;

use App\Data\Auth\AuthenticationResult;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class AuthenticationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof AuthenticationResult) {
            throw new LogicException('AuthenticationResource requires an AuthenticationResult.');
        }

        $authentication = $this->resource;
        $organizations = $authentication->user->organizations
            ->map(fn (Organization $organization): array => [
                'id' => $organization->getKey(),
                'name' => $organization->name,
                'slug' => $organization->slug,
                'role' => $organization->membership->role->value,
            ])
            ->values();

        $userWithoutOrganizations = clone $authentication->user;
        $userWithoutOrganizations->unsetRelation('organizations');

        return [
            'user' => (new UserResource($userWithoutOrganizations))->resolve($request),
            'organizations' => $organizations,
            'token' => $authentication->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}
