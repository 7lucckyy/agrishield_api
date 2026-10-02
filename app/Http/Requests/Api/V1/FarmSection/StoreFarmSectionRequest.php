<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\FarmSection;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StoreFarmSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $farm = $this->route('farm');

        if (! $user instanceof User || ! $farm instanceof Farm) {
            return false;
        }

        Gate::forUser($user)->authorize('update', $farm);

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
            'name' => [
                'required',
                'string',
                'between:1,80',
                Rule::unique(FarmSection::class, 'name')->where('farm_id', $this->route('farm')?->getKey()),
            ],
            'crop_id' => [
                'required',
                'integer',
                Rule::exists(Crop::class, 'id')->where('active', true),
            ],
            'area_hectares' => ['required', 'numeric', 'min:0.0001', 'max:1000000'],
            'position' => ['sometimes', 'integer', 'between:1,999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name', '')),
            'notes' => $this->filled('notes') ? Str::squish((string) $this->input('notes')) : null,
        ]);
    }
}
