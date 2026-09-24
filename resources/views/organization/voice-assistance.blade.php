@extends('layouts.admin')
@section('title', 'Field Voice')
@section('eyebrow', 'Farmer support')
@section('page-title', 'Field Voice')
@php($area = 'organization')
@section('content')
@if(session('status'))<div class="admin-alert success">{{ session('status') }}</div>@endif
<div class="voice-workspace-head"><div><p class="admin-eyebrow">Voice intake</p><h1>Listen, translate, respond.</h1><p>Capture a farmer question in their own voice and retain the response as an extension case.</p></div><span class="privacy-chip"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5z"/></svg> Private audio</span></div>

<div class="voice-workspace-grid">
    <form class="voice-recorder-card" method="POST" action="{{ route('organization.voice-assistance.store', $organization) }}" enctype="multipart/form-data" data-voice-recorder>
        @csrf
        <div class="recorder-status"><span>NEW FIELD QUESTION</span><strong data-recording-status>Ready to record</strong></div>
        <div class="recorder-control">
            <button type="button" class="record-button" data-record-button aria-label="Start recording"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 4a5 5 0 0 0-5 5v7a5 5 0 0 0 10 0V9a5 5 0 0 0-5-5Z"/><path d="M7 15v1a9 9 0 0 0 18 0v-1M16 25v4M11 29h10"/></svg></button>
            <button type="button" class="stop-button" data-stop-button disabled>Stop recording</button>
            <time data-recording-timer>00:00</time>
        </div>
        <div class="recording-preview" data-recording-preview hidden><audio controls data-audio-preview></audio></div>
        <label class="voice-upload"><span>Or upload a voice note</span><input type="file" name="audio" accept="audio/*,.webm" data-audio-input required><small>MP3, M4A, WAV, OGG or WebM · up to 20 MB</small></label>
        @error('audio')<p class="form-error">{{ $message }}</p>@enderror
        <div class="voice-form-row"><label><span>Spoken language</span><select name="source_language" required>@foreach($languages as $code => $language)<option value="{{ $code }}" @selected(old('source_language', 'auto') === $code)>{{ $language }}</option>@endforeach</select></label><label><span>Reply language</span><select name="response_language" required>@foreach($languages as $code => $language)@if($code !== 'auto')<option value="{{ $code }}" @selected(old('response_language', 'en') === $code)>{{ $language }}</option>@endif @endforeach</select></label></div>
        <label class="voice-farm-select"><span>Link to a farm <small>Optional</small></span><select name="farm_id"><option value="">No farm selected</option>@foreach($farms as $farm)<option value="{{ $farm->id }}" @selected((int) old('farm_id') === $farm->id)>{{ $farm->name }}</option>@endforeach</select></label>
        <div class="recorder-foot"><p>By submitting, you confirm the farmer agreed to this recording being used for advisory support.</p><button class="admin-primary-button" type="submit">Translate and prepare guidance</button></div>
    </form>
    <aside class="voice-guardrails"><span>FIELD GUARDRAILS</span><h2>Good guidance leaves room for judgement.</h2><ul><li><i>01</i><div><strong>Check the translation</strong><p>Keep the original transcript beside the translated version.</p></div></li><li><i>02</i><div><strong>Confirm before treatment</strong><p>Use images, field observations or an extension visit for diagnosis.</p></div></li><li><i>03</i><div><strong>Escalate urgent risk</strong><p>Poisoning, severe illness and immediate danger need local professional help.</p></div></li></ul></aside>
</div>

<section class="voice-case-section"><div class="voice-case-heading"><div><span>RECENT CASES</span><h2>Organisation voice history</h2></div><p>{{ $voiceRequests->total() }} {{ Str::plural('case', $voiceRequests->total()) }} retained</p></div>
    <div class="voice-case-list">@forelse($voiceRequests as $voiceRequest)<article class="voice-case"><div class="voice-case-meta"><span class="voice-status {{ $voiceRequest->status->value }}">{{ $voiceRequest->status->value }}</span><time>{{ $voiceRequest->created_at->format('d M Y · H:i') }}</time><span>{{ $voiceRequest->user->name }}</span>@if($voiceRequest->farm)<span>{{ $voiceRequest->farm->name }}</span>@endif<a href="{{ route('organization.voice-assistance.audio', [$organization, $voiceRequest]) }}">Play original note</a></div><div class="voice-case-content"><div><small>Original transcript</small><p>{{ $voiceRequest->transcript ?? 'Transcript unavailable.' }}</p></div><div><small>Translation</small><p>{{ $voiceRequest->translated_transcript ?? 'Translation unavailable.' }}</p></div><div class="voice-case-guidance"><small>Field guidance</small><p>{{ $voiceRequest->guidance ?? $voiceRequest->failure_reason }}</p>@if($voiceRequest->safety_note)<span>{{ $voiceRequest->safety_note }}</span>@endif</div></div></article>@empty<div class="voice-empty"><strong>No voice cases yet.</strong><p>Record the first farmer question to begin the organisation history.</p></div>@endforelse</div>
    {{ $voiceRequests->links() }}
</section>
@endsection
