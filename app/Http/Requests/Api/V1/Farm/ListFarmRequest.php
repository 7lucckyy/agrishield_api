<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Farm;

use App\Enums\FarmStatus;
use App\Enums\ProviderStatus;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

final class ListFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('viewAny', Farm::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:organization_id,status,crop_id,search,provider_status'],
            'filter.organization_id' => ['sometimes', 'integer'],
            'filter.status' => ['sometimes', Rule::enum(FarmStatus::class)],
            'filter.crop_id' => ['sometimes', 'integer', Rule::exists(Crop::class, 'id')],
            'filter.search' => ['sometimes', 'string', 'max:255'],
            'filter.provider_status' => ['sometimes', Rule::enum(ProviderStatus::class)],
            'sort' => ['sometimes', Rule::in(['name', '-name', 'area_hectares', '-area_hectares', 'created_at', '-created_at'])],
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
        foreach (['organization_id', 'crop_id'] as $integerFilter) {
            if (isset($filters[$integerFilter]) && is_numeric($filters[$integerFilter])) {
                $filters[$integerFilter] = (int) $filters[$integerFilter];
            }
        }

        return $filters;
    }

    /** @return array{field: string, direction: 'asc'|'desc'} */
    public function sort(): array
    {
        $sort = (string) Arr::get($this->validated(), 'sort', '-created_at');

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
