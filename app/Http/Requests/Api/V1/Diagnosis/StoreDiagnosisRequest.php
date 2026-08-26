<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Diagnosis;

use App\Models\Farm;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Imagick;
use Throwable;

final class StoreDiagnosisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $farm = $this->route('farm');

        return $farm instanceof Farm && $this->user()?->can('view', $farm) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'max:8192'],
            'farm_crop_cycle_id' => ['nullable', 'integer', Rule::exists('farm_crop_cycles', 'id')->where('farm_id', $this->farmId())],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $image = $this->file('image');
            if (! $image instanceof UploadedFile || ! $image->isValid()) {
                return;
            }

            $mime = $image->getMimeType();
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'], true)) {
                $validator->errors()->add('image', 'The image content type is not supported.');

                return;
            }

            $contents = file_get_contents($image->getRealPath());
            if ($contents === false || str_contains($contents, '<?php') || str_contains($contents, '<?=') || str_contains($contents, '<script')) {
                $validator->errors()->add('image', 'The image contains unsafe embedded content.');

                return;
            }

            try {
                $metadata = new Imagick;
                $metadata->pingImage($image->getRealPath());
                $width = $metadata->getImageWidth();
                $height = $metadata->getImageHeight();
                $metadata->clear();

                if ($width < 224 || $height < 224 || $width > 8000 || $height > 8000) {
                    $validator->errors()->add('image', 'The image dimensions must be between 224 and 8000 pixels.');
                }
            } catch (Throwable) {
                $validator->errors()->add('image', 'The uploaded file is not a readable image.');
            }
        }];
    }

    private function farmId(): int
    {
        $farm = $this->route('farm');

        return $farm instanceof Farm ? $farm->getKey() : 0;
    }
}
