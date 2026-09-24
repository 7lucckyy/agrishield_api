@extends('layouts.admin', ['area' => 'organization', 'organization' => $organization])
@section('title', $farm->name)
@section('eyebrow', $organization->name)
@section('page-title', $farm->name)

@section('content')
@if(session('status'))
    <div class="admin-alert success">{{ session('status') }}</div>
@endif

<section class="page-lead compact">
    <div>
        <p class="eyebrow"><span></span> {{ collect([$farm->locality, $farm->state])->filter()->join(', ') ?: 'Registered field' }}</p>
        <h1>{{ $farm->name }}</h1>
        <p>{{ number_format((float) $farm->area_hectares, 2) }} hectares · Owned by {{ $farm->owner->name }}</p>
    </div>
    <span class="status-pill large status-{{ $farm->provider_status->value }}">{{ str($farm->provider_status->value)->headline() }}</span>
</section>

<div class="farm-detail-grid">
    <section class="panel farm-map-panel">
        <div class="panel-head"><div><span>Boundary</span><h2>Field location</h2></div><small>{{ $farm->centroid_latitude }}, {{ $farm->centroid_longitude }}</small></div>
        <div class="abstract-map"><span class="field-shape xlarge"></span><i></i><b>{{ number_format((float) $farm->area_hectares, 1) }} ha</b></div>
    </section>
    <section class="panel">
        <div class="panel-head"><div><span>Current cycle</span><h2>{{ $farm->activeCropCycle?->crop?->name ?? 'No active crop' }}</h2></div></div>
        <dl class="detail-list">
            <div><dt>Farm status</dt><dd>{{ str($farm->status->value)->headline() }}</dd></div>
            <div><dt>Provider status</dt><dd>{{ str($farm->provider_status->value)->headline() }}</dd></div>
            <div><dt>Last synchronized</dt><dd>{{ $farm->last_synced_at?->diffForHumans() ?? 'Never' }}</dd></div>
            <div><dt>Country</dt><dd>{{ $farm->country ?? '—' }}</dd></div>
        </dl>
    </section>
</div>

<section class="crop-screening panel" aria-labelledby="crop-screening-title">
    <div class="crop-screening-intro">
        <p class="admin-eyebrow">Crop image screening</p>
        <h2 id="crop-screening-title">Check visible crop symptoms.</h2>
        <p>Upload one clear photo of the affected leaf or plant. AgriShield will identify possible visual signs and prepare a practical next step for field review.</p>
        <ul>
            <li>Use daylight and keep the affected area in focus.</li>
            <li>Include one close image; avoid screenshots and collages.</li>
            <li>This is screening support, not a laboratory diagnosis.</li>
        </ul>
    </div>
    <form class="crop-screening-form" method="POST" action="{{ route('organization.farms.diagnoses.store', [$organization, $farm]) }}" enctype="multipart/form-data">
        @csrf
        @if($farm->activeCropCycle)
            <input type="hidden" name="farm_crop_cycle_id" value="{{ $farm->activeCropCycle->id }}">
        @endif
        <label class="crop-photo-input">
            <span>Crop photo <b>Required</b></span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" required>
            <small>JPEG, PNG, WebP or HEIC · 8 MB maximum</small>
        </label>
        @error('image')<p class="form-error">{{ $message }}</p>@enderror
        <label class="crop-note-input">
            <span>What did you observe? <small>Optional</small></span>
            <textarea name="note" rows="4" maxlength="1000" placeholder="Example: Yellow streaks started on the lower maize leaves after heavy rain.">{{ old('note') }}</textarea>
        </label>
        @error('note')<p class="form-error">{{ $message }}</p>@enderror
        <div class="crop-screening-submit">
            <p>The image remains private to authorised farm users. Confirm treatment decisions with an agronomist or extension officer.</p>
            <button class="admin-primary-button" type="submit">Screen crop photo</button>
        </div>
    </form>
</section>

<section class="crop-case-section" aria-labelledby="crop-case-title">
    <div class="voice-case-heading">
        <div><span>RECENT SCREENINGS</span><h2 id="crop-case-title">Crop health cases</h2></div>
        <p>{{ $farm->diagnosisRequests->count() }} recent {{ str('case')->plural($farm->diagnosisRequests->count()) }}</p>
    </div>
    <div class="crop-case-list">
        @forelse($farm->diagnosisRequests as $diagnosis)
            @php($imageUrl = URL::temporarySignedRoute('media.diagnosis.image', now()->addMinutes(5), ['diagnosis' => $diagnosis]))
            <article class="crop-case">
                <img src="{{ $imageUrl }}" alt="Crop photo submitted for screening on {{ $diagnosis->created_at->format('d M Y') }}" width="160" height="132" loading="lazy">
                <div class="crop-case-body">
                    <div class="crop-case-meta">
                        <span class="voice-status {{ $diagnosis->status->value }}">{{ str($diagnosis->status->value)->headline() }}</span>
                        <time>{{ $diagnosis->created_at->format('d M Y · H:i') }}</time>
                        <span>{{ $diagnosis->requestedBy->name }}</span>
                        @if($diagnosis->cropCycle?->crop)<span>{{ $diagnosis->cropCycle->crop->name }}</span>@endif
                    </div>
                    @if($diagnosis->status === App\Enums\DiagnosisStatus::Completed)
                        <div class="crop-case-result">
                            <div>
                                <small>Possible condition</small>
                                <h3>{{ $diagnosis->diagnosis }}</h3>
                                @if($diagnosis->confidence !== null)<span>{{ round((float) $diagnosis->confidence * 100) }}% model confidence</span>@endif
                            </div>
                            <div><small>Recommended next step</small><p>{{ $diagnosis->recommendation }}</p></div>
                        </div>
                        <p class="crop-case-review">{{ $diagnosis->reviewed_at ? 'Reviewed by an AgriShield agronomist.' : 'AI screening · Expert review still recommended.' }}</p>
                    @elseif($diagnosis->status === App\Enums\DiagnosisStatus::Failed || $diagnosis->status === App\Enums\DiagnosisStatus::Expired)
                        <div class="crop-case-state error"><strong>Screening could not be completed.</strong><span>Upload another clear photo or ask an extension officer to inspect the crop.</span></div>
                    @else
                        <div class="crop-case-state"><strong>Screening in progress.</strong><span>The result will appear here after the image has been checked.</span></div>
                    @endif
                    @if($diagnosis->note)<p class="crop-case-note"><strong>Field note:</strong> {{ $diagnosis->note }}</p>@endif
                </div>
            </article>
        @empty
            <div class="voice-empty"><strong>No crop screenings yet.</strong><p>Upload the first field photo to start a traceable crop health case.</p></div>
        @endforelse
    </div>
</section>

<div class="dashboard-grid">
    <section class="panel panel-wide">
        <div class="panel-head"><div><span>Remote sensing</span><h2>Latest observations</h2></div></div>
        <div class="activity-list">
            @forelse($farm->satelliteObservations as $observation)
                <div><span class="activity-icon tone-green">↗</span><p><strong>{{ str($observation->metric_type->value)->upper() }}</strong><span>{{ $observation->captured_at?->format('d M Y, H:i') }}</span></p><b>{{ number_format((float) $observation->value, 3) }}</b><span class="status-pill status-{{ $observation->quality_flag?->value ?? 'unavailable' }}">{{ str($observation->quality_flag?->value ?? 'Unavailable')->headline() }}</span></div>
            @empty
                <div class="empty-state"><strong>No observations yet</strong><span>Satellite readings appear after the first sync.</span></div>
            @endforelse
        </div>
    </section>
    <aside class="panel weather-panel">
        <div class="panel-head"><div><span>Forecast</span><h2>Next seven days</h2></div></div>
        @forelse($farm->weatherForecasts as $forecast)
            <div><time>{{ $forecast->forecast_date->format('D') }}</time><span>☁</span><strong>{{ number_format((float) $forecast->temperature_max) }}°</strong><small>{{ number_format((float) $forecast->rainfall_probability) }}% rain</small></div>
        @empty
            <div class="empty-state"><strong>No forecast yet</strong><span>Weather appears after synchronization.</span></div>
        @endforelse
    </aside>
</div>
@endsection
