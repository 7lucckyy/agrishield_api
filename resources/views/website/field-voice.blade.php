@extends('layouts.marketing')
@section('title', 'Field Voice | AgriShield AI')
@section('description', 'Field Voice securely captures and routes crop questions for farmers and extension teams across Northern Nigeria.')
@section('content')
<section class="voice-page-hero">
    <p class="field-kicker"><span></span> Field Voice / intake ready</p>
    <h1>Capture the question. Keep the context.</h1>
    <p>Field Voice records or uploads a farmer’s crop question, links it to a farm and retains a private case history for the team responsible for follow-through.</p>
</section>
<section class="voice-page-demo field-section">
    <div class="voice-demo-copy"><p>AVAILABLE WORKFLOW</p><h2>Built for a phone in the field.</h2><ol><li><span>01</span><div><strong>Record or upload</strong><p>Capture a short voice note in the browser or use a saved audio file.</p></div></li><li><span>02</span><div><strong>Attach farm context</strong><p>Link the question to a permitted farm and selected source and target languages.</p></div></li><li><span>03</span><div><strong>Keep the case private</strong><p>Protected audio and request history remain inside the organisation workspace.</p></div></li><li><span>04</span><div><strong>Route for review</strong><p>The record gives a field team a clear place to review and continue the case.</p></div></li></ol></div>
    <div class="voice-demo-panel"><div class="voice-demo-badge">SECURE ORGANISATION WORKSPACE</div><div class="voice-demo-circle"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 4a5 5 0 0 0-5 5v7a5 5 0 0 0 10 0V9a5 5 0 0 0-5-5Z"/><path d="M7 15v1a9 9 0 0 0 18 0v-1M16 25v4M11 29h10"/></svg></div><h3>Voice intake is available now</h3><p>Automatic transcription, translation and generated guidance activate only when a production voice provider is configured.</p>@auth @if(auth()->user()->primaryOrganizationId())<a class="field-button field-button-yellow" href="{{ route('organization.voice-assistance.index', auth()->user()->primaryOrganizationId()) }}">Open Field Voice</a>@endif @else<a class="field-button field-button-yellow" href="{{ route('login') }}">Sign in to continue</a>@endauth</div>
</section>
<section class="voice-safety field-section"><div><p>PUBLISHED BOUNDARIES</p><h2>What the current feature does—and depends on.</h2></div><div class="voice-safety-grid"><article><strong>Core platform</strong><ul><li>Private audio intake and playback</li><li>Farm and language selection</li><li>Organisation-scoped history</li><li>Status and failure visibility</li></ul></article><article><strong>Configured provider</strong><ul><li>Speech transcription</li><li>Language translation</li><li>Generated crop guidance</li><li>Automated escalation signals</li></ul></article></div></section>
@endsection
