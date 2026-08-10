<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

final class ListOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $organization = $this->route('organization');
        if (! $user instanceof User || ! $organization instanceof Organization) {
            return false;
        }

        Gate::forUser($user)->authorize('viewMembers', $organization);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) Arr::get($this->validated(), 'per_page', 25);
    }
}
