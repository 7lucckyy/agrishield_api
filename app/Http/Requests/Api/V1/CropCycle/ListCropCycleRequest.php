<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\CropCycle;

use App\Enums\CropCycleStatus;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ListCropCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $farm = $this->route('farm');
        if (! $user instanceof User || ! $farm instanceof Farm) {
            return false;
        }

        Gate::forUser($user)->authorize('view', $farm);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:status'],
            'filter.status' => ['sometimes', Rule::enum(CropCycleStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function status(): ?CropCycleStatus
    {
        $status = Arr::get($this->validated(), 'filter.status');

        return is_string($status) ? CropCycleStatus::from($status) : null;
    }

    public function perPage(): int
    {
        return (int) Arr::get($this->validated(), 'per_page', 25);
    }
}
