<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\CropCycle;

use App\Enums\CropCycleStatus;
use App\Models\CropCycle;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateCropCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $cropCycle = $this->route('cropCycle');
        if (! $user instanceof User || ! $cropCycle instanceof CropCycle) {
            return false;
        }

        Gate::forUser($user)->authorize('update', $cropCycle);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'planting_date' => ['sometimes', 'date', 'after_or_equal:'.now()->subYears(2)->toDateString(), 'before_or_equal:'.now()->addYear()->toDateString()],
            'expected_harvest_date' => ['sometimes', 'nullable', 'date'],
            'actual_harvest_date' => ['sometimes', 'nullable', 'date'],
            'season' => ['sometimes', 'nullable', 'string', 'max:40'],
            'status' => ['sometimes', Rule::enum(CropCycleStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['planting_date', 'expected_harvest_date'])) {
                return;
            }

            $cycle = $this->route('cropCycle');
            if (! $cycle instanceof CropCycle) {
                return;
            }

            $plantingDate = $this->filled('planting_date')
                ? Carbon::parse((string) $this->input('planting_date'))
                : $cycle->planting_date;
            $expectedHarvestDate = $this->exists('expected_harvest_date')
                ? ($this->filled('expected_harvest_date')
                    ? Carbon::parse((string) $this->input('expected_harvest_date'))
                    : null)
                : $cycle->expected_harvest_date;

            if ($plantingDate === null || $expectedHarvestDate === null) {
                return;
            }

            if ($expectedHarvestDate->lt($plantingDate)) {
                $validator->errors()->add(
                    'expected_harvest_date',
                    'The expected harvest date must be on or after the planting date.',
                );
            }

            if ($expectedHarvestDate->gt($plantingDate->copy()->addDays(730))) {
                $validator->errors()->add(
                    'expected_harvest_date',
                    'The expected harvest date must be within 730 days of the planting date.',
                );
            }
        }];
    }
}
