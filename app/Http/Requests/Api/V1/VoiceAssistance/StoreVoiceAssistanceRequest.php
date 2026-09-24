<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\VoiceAssistance;

use App\Models\Farm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVoiceAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->filled('farm_id')) {
            return true;
        }

        $farm = Farm::query()->where('uuid', $this->string('farm_id')->toString())->first();

        return $farm !== null && $this->user()?->can('view', $farm) === true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'max:20480', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/ogg,audio/webm,video/webm'],
            'source_language' => ['required', 'string', Rule::in(array_keys(config('voice-assistance.languages')))],
            'response_language' => ['required', 'string', Rule::in(array_keys(config('voice-assistance.languages'))), Rule::notIn(['auto'])],
            'farm_id' => ['nullable', 'uuid', 'exists:farms,uuid'],
        ];
    }
}
