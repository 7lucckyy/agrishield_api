<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Farm;

use App\Data\Geometry\ProcessedGeometry;
use App\Enums\GeometryValidationError;
use App\Exceptions\InvalidGeometryException;
use App\Models\Farm;
use App\Models\User;
use App\Services\Geometry\GeometryProcessor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use LogicException;

final class StoreFarmRequest extends FormRequest
{
    private ?ProcessedGeometry $processedGeometry = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('create', Farm::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(GeometryProcessor $geometryProcessor): array
    {
        return [
            'name' => ['required', 'string', 'between:2,255'],
            'boundary_geojson' => [
                'required',
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
            'locality' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2', 'alpha:ascii'],
            'organization_id' => ['nullable', 'integer'],
        ];
    }

    public function processedGeometry(): ProcessedGeometry
    {
        return $this->processedGeometry
            ?? throw new LogicException('The farm boundary has not been validated.');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name', '')),
            'locality' => $this->filled('locality') ? Str::squish((string) $this->input('locality')) : null,
            'state' => $this->filled('state') ? Str::squish((string) $this->input('state')) : null,
            'country' => $this->filled('country') ? Str::upper((string) $this->input('country')) : null,
        ]);
    }
}
