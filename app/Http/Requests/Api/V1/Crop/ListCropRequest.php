<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Crop;

use App\Enums\CropCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

final class ListCropRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:active,category,search'],
            'filter.active' => ['sometimes', 'boolean'],
            'filter.category' => ['sometimes', 'nullable', Rule::enum(CropCategory::class)],
            'filter.search' => ['sometimes', 'nullable', 'string', 'max:160'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', Rule::in(['name', '-name'])],
        ];
    }

    public function active(): bool
    {
        return (bool) Arr::get($this->validated(), 'filter.active', true);
    }

    public function category(): ?CropCategory
    {
        $category = Arr::get($this->validated(), 'filter.category');

        return is_string($category) ? CropCategory::tryFrom($category) : null;
    }

    public function search(): ?string
    {
        $search = Arr::get($this->validated(), 'filter.search');

        return is_string($search) && $search !== '' ? $search : null;
    }

    public function perPage(): int
    {
        return (int) Arr::get($this->validated(), 'per_page', 25);
    }

    public function page(): int
    {
        return (int) Arr::get($this->validated(), 'page', 1);
    }

    public function descending(): bool
    {
        return Arr::get($this->validated(), 'sort', 'name') === '-name';
    }

    protected function prepareForValidation(): void
    {
        $filters = $this->input('filter', []);

        if (! is_array($filters)) {
            return;
        }

        if ($this->has('filter.active')) {
            $filters['active'] = $this->boolean('filter.active');
        }

        if ($this->has('filter.category')) {
            $filters['category'] = $this->string('filter.category')->trim()->lower()->value();
        }

        if ($this->has('filter.search')) {
            $filters['search'] = (string) $this->string('filter.search')->trim();
        }

        $this->merge(['filter' => $filters]);
    }
}
