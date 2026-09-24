<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Organization;

use App\Models\Farm;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVoiceAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->route('organization');
        if (! $organization instanceof Organization || $this->user()?->can('viewOverview', $organization) !== true) {
            return false;
        }

        return ! $this->filled('farm_id') || Farm::query()->whereBelongsTo($organization)->whereKey($this->integer('farm_id'))->exists();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'max:20480', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/ogg,audio/webm,video/webm'],
            'source_language' => ['required', 'string', Rule::in(array_keys(config('voice-assistance.languages')))],
            'response_language' => ['required', 'string', Rule::in(array_keys(config('voice-assistance.languages'))), Rule::notIn(['auto'])],
            'farm_id' => ['nullable', 'integer', Rule::exists('farms', 'id')->where('organization_id', $this->organizationId())],
        ];
    }

    private function organizationId(): int
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization ? $organization->getKey() : 0;
    }
}
