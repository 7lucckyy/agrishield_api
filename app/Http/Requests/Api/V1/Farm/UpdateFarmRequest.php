<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Farm;

use App\Data\Geometry\ProcessedGeometry;
use App\Enums\FarmStatus;
use App\Enums\GeometryValidationError;
use App\Exceptions\InvalidGeometryException;
use App\Models\Farm;
use App\Models\User;
use App\Services\Geometry\GeometryProcessor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpdateFarmRequest extends FormRequest
{
    private ?ProcessedGeometry $processedGeometry = null;

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

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(GeometryProcessor $geometryProcessor): array
    {
        return [
            'name' => ['sometimes', 'string', 'between:2,255'],
            'boundary_geojson' => [
                'sometimes',
                'array',
                function (string $attribute, mixed $value, Closure $fail) use ($geometryProcessor): void {
                    $encoded = json_encode($value);
                    if (! is_string($encoded) || strlen($encoded) > 512 * 1024) {
                        $fail(GeometryValidationError::TooLarge->message());

                        return;
                    }

                    try {
                        $this->processedGeometry = $geometryProcessor->process($value);
                    } catch (InvalidGeometryException $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],
            'locality' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2', 'alpha:ascii'],
            'status' => ['sometimes', Rule::enum(FarmStatus::class)],
        ];
    }

    public function processedGeometry(): ?ProcessedGeometry
    {
        return $this->processedGeometry;
    }

    protected function prepareForValidation(): void
    {
        $normalised = [];
        foreach (['name', 'locality', 'state'] as $field) {
            if ($this->has($field)) {
                $normalised[$field] = $this->filled($field)
                    ? Str::squish((string) $this->input($field))
                    : null;
            }
        }

        if ($this->has('country')) {
            $normalised['country'] = $this->filled('country')
                ? Str::upper((string) $this->input('country'))
                : null;
        }

        $this->merge($normalised);
    }
}
