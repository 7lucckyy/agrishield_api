<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Organization;

use App\Enums\FarmStatus;
use App\Enums\ProviderStatus;
use App\Models\Crop;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ListOrganizationFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $organization = $this->route('organization');
        if (! $user instanceof User || ! $organization instanceof Organization) {
            return false;
        }

        Gate::forUser($user)->authorize('viewFarms', $organization);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:status,crop_id,search,provider_status'],
            'filter.status' => ['sometimes', Rule::enum(FarmStatus::class)],
            'filter.crop_id' => ['sometimes', 'integer', Rule::exists(Crop::class, 'id')],
            'filter.search' => ['sometimes', 'string', 'max:255'],
            'filter.provider_status' => ['sometimes', Rule::enum(ProviderStatus::class)],
            'sort' => ['sometimes', Rule::in(['name', '-name', 'area_hectares', '-area_hectares', 'last_synced_at', '-last_synced_at'])],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $filters = Arr::get($this->validated(), 'filter', []);
        if (! is_array($filters)) {
            return [];
        }

        if (isset($filters['status']) && is_string($filters['status'])) {
            $filters['status'] = FarmStatus::from($filters['status']);
        }
        if (isset($filters['provider_status']) && is_string($filters['provider_status'])) {
            $filters['provider_status'] = ProviderStatus::from($filters['provider_status']);
        }
        if (isset($filters['crop_id']) && is_numeric($filters['crop_id'])) {
            $filters['crop_id'] = (int) $filters['crop_id'];
        }

        return $filters;
    }

    /** @return array{field: string, direction: 'asc'|'desc'} */
    public function sort(): array
    {
        $sort = (string) Arr::get($this->validated(), 'sort', 'name');

        return [
            'field' => ltrim($sort, '-'),
            'direction' => str_starts_with($sort, '-') ? 'desc' : 'asc',
        ];
    }

    public function perPage(): int
    {
        return (int) Arr::get($this->validated(), 'per_page', 25);
    }
}
