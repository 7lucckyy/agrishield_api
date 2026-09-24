<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Organization;

use App\Actions\VoiceAssistance\CreateVoiceAssistanceRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Organization\StoreVoiceAssistanceRequest;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use App\Models\VoiceAssistanceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

final class VoiceAssistanceController extends Controller
{
    public function index(Organization $organization): View
    {
        Gate::authorize('viewOverview', $organization);

        return view('organization.voice-assistance', [
            'organization' => $organization,
            'languages' => config('voice-assistance.languages'),
            'responseLanguages' => config('voice-assistance.response_languages'),
            'farms' => $organization->farms()->orderBy('name')->get(['id', 'uuid', 'name']),
            'voiceRequests' => VoiceAssistanceRequest::query()
                ->whereBelongsTo($organization)
                ->with(['farm:id,uuid,name', 'user:id,name'])
                ->latest()
                ->paginate(12),
        ]);
    }

    public function store(StoreVoiceAssistanceRequest $request, Organization $organization, CreateVoiceAssistanceRequest $create): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $audio */
        $audio = $request->file('audio');
        $farm = $request->filled('farm_id') ? Farm::query()->whereBelongsTo($organization)->findOrFail($request->integer('farm_id')) : null;
        $voiceRequest = $create->execute($user, $audio, $request->string('source_language')->toString(), $request->string('response_language')->toString(), $farm, $organization);

        return to_route('organization.voice-assistance.index', $organization)
            ->with('status', $voiceRequest->status->value === 'completed' ? 'Voice note translated and guidance prepared.' : 'Voice note received. Translation and guidance are being prepared.');
    }
}
