<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\CropCycle;

use App\Enums\CropCycleStatus;
use App\Models\Crop;
use App\Models\CropCycle;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreCropCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $farm = $this->route('farm');
        if (! $user instanceof User || ! $farm instanceof Farm) {
            return false;
        }

        Gate::forUser($user)->authorize('create', [CropCycle::class, $farm]);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'crop_id' => [
                'required',
                'integer',
                Rule::exists(Crop::class, 'id')->where('active', true),
            ],
            'planting_date' => ['required', 'date', 'after_or_equal:'.now()->subYears(2)->toDateString(), 'before_or_equal:'.now()->addYear()->toDateString()],
            'expected_harvest_date' => ['nullable', 'date', 'after_or_equal:planting_date'],
            'season' => ['nullable', 'string', 'max:40'],
            'status' => ['sometimes', Rule::in([CropCycleStatus::Planned->value, CropCycleStatus::Active->value])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['planting_date', 'expected_harvest_date'])
                || ! $this->filled('expected_harvest_date')) {
                return;
            }

            $plantingDate = Carbon::parse((string) $this->input('planting_date'));
            $expectedHarvestDate = Carbon::parse((string) $this->input('expected_harvest_date'));

            if ($expectedHarvestDate->gt($plantingDate->copy()->addDays(730))) {
                $validator->errors()->add(
                    'expected_harvest_date',
                    'The expected harvest date must be within 730 days of the planting date.',
                );
            }
        }];
    }
}
