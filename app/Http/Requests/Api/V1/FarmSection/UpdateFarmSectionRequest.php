<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\FarmSection;

use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateFarmSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $farm = $this->route('farm');
        $farmSection = $this->route('farmSection');

        if (! $user instanceof User
            || ! $farm instanceof Farm
            || ! $farmSection instanceof FarmSection
            || $farmSection->farm_id !== $farm->getKey()) {
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
                'sometimes',
                'string',
                'between:1,80',
                Rule::unique(FarmSection::class, 'name')
                    ->where('farm_id', $this->route('farm')?->getKey())
                    ->ignore($this->route('farmSection')),
            ],
            'crop_id' => [
                'sometimes',
                'integer',
                Rule::exists(Crop::class, 'id')->where('active', true),
            ],
            'area_hectares' => ['sometimes', 'numeric', 'min:0.0001', 'max:1000000'],
            'position' => ['sometimes', 'integer', 'between:1,999'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        if ($this->has('name')) {
            $values['name'] = Str::squish((string) $this->input('name'));
        }

        if ($this->has('notes')) {
            $values['notes'] = $this->filled('notes') ? Str::squish((string) $this->input('notes')) : null;
        }

        $this->merge($values);
    }
}
