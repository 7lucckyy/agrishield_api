<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organization;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $organization = $this->route('organization');
        if (! $actor instanceof User || ! $organization instanceof Organization) {
            return false;
        }

        Gate::forUser($actor)->authorize('updateMemberRole', $organization);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(OrganizationRole::class)],
            'cluster_name' => ['nullable', 'required_if:role,cluster_lead', 'string', 'between:2,120'],
        ];
    }

    public function role(): OrganizationRole
    {
        return OrganizationRole::from($this->string('role')->toString());
    }

    public function clusterName(): ?string
    {
        return $this->filled('cluster_name') ? Str::squish($this->string('cluster_name')->toString()) : null;
    }
}
