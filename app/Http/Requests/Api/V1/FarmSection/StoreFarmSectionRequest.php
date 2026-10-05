<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\FarmSection;

use App\Data\Geometry\ProcessedGeometry;
use App\Enums\FarmSectionStatus;
use App\Enums\GeometryValidationError;
use App\Exceptions\InvalidGeometryException;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\FarmSection;
use App\Models\User;
use App\Services\Geometry\GeometryProcessor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreFarmSectionRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(GeometryProcessor $geometryProcessor): array
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
            'area_hectares' => ['required_without:boundary_geojson', 'numeric', 'min:0.0001', 'max:1000000'],
            'position' => ['sometimes', 'integer', 'between:1,999'],
            'status' => ['sometimes', Rule::enum(FarmSectionStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function processedGeometry(): ?ProcessedGeometry
    {
        return $this->processedGeometry;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name', '')),
            'notes' => $this->filled('notes') ? Str::squish((string) $this->input('notes')) : null,
        ]);
    }
}
