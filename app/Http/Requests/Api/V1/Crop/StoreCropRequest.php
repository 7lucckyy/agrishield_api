<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Crop;

use App\Enums\CropCategory;
use App\Models\Crop;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreCropRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('create', Crop::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique(Crop::class)],
            'scientific_name' => ['nullable', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:32', 'alpha_dash:ascii', Rule::unique(Crop::class)],
            'category' => ['nullable', Rule::enum(CropCategory::class)],
            'default_cycle_days' => ['nullable', 'integer', 'between:1,730'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name', '')),
            'scientific_name' => $this->filled('scientific_name')
                ? Str::squish((string) $this->input('scientific_name'))
                : null,
            'code' => Str::upper(Str::squish((string) $this->input('code', ''))),
            'category' => $this->filled('category')
                ? Str::lower((string) $this->input('category'))
                : null,
        ]);
    }
}
