<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Sync;

use App\Enums\SyncType;
use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TriggerFarmSyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $farm = $this->route('farm');

        return $farm instanceof Farm && $this->user()?->can('update', $farm) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'types' => ['sometimes', 'array', 'min:1'],
            'types.*' => ['distinct', Rule::in(array_map(
                static fn (SyncType $type): string => $type->value,
                $this->availableTypes(),
            ))],
        ];
    }

    /** @return list<SyncType> */
    public function syncTypes(): array
    {
        $values = $this->validated('types');

        return is_array($values)
            ? array_map(static fn (string $value): SyncType => SyncType::from($value), $values)
            : $this->availableTypes();
    }

    /** @return list<SyncType> */
    private function availableTypes(): array
    {
        return [SyncType::SoilHealth, SyncType::Weather, SyncType::CropHealth, SyncType::WaterStress,
            SyncType::SoilMoisture, SyncType::IrrigationAdvisory, SyncType::PestForewarning, SyncType::CropPractices];
    }
}
