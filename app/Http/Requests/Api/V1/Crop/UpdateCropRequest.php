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

final class UpdateCropRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $crop = $this->route('crop');

        return $user instanceof User
            && $crop instanceof Crop
            && $user->can('update', $crop);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $crop = $this->route('crop');
        $uniqueName = Rule::unique(Crop::class);
        $uniqueCode = Rule::unique(Crop::class);

        if ($crop instanceof Crop) {
            $uniqueName->ignore($crop);
            $uniqueCode->ignore($crop);
        }

        return [
            'name' => ['sometimes', 'string', 'max:120', $uniqueName],
            'scientific_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'code' => ['sometimes', 'string', 'max:32', 'alpha_dash:ascii', $uniqueCode],
            'category' => ['sometimes', 'nullable', Rule::enum(CropCategory::class)],
            'default_cycle_days' => ['sometimes', 'nullable', 'integer', 'between:1,730'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('name')) {
            $normalized['name'] = Str::squish((string) $this->input('name'));
        }

        if ($this->has('scientific_name')) {
            $normalized['scientific_name'] = $this->filled('scientific_name')
                ? Str::squish((string) $this->input('scientific_name'))
                : null;
        }

        if ($this->has('code')) {
            $normalized['code'] = Str::upper(Str::squish((string) $this->input('code')));
        }

        if ($this->has('category')) {
            $normalized['category'] = $this->filled('category')
                ? Str::lower((string) $this->input('category'))
                : null;
        }

        $this->merge($normalized);
    }
}
