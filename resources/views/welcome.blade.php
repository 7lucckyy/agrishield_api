@extends('layouts.marketing')
@section('title', 'AgriShield AI | Crop support that reaches the field')
@section('description', 'AgriShield connects farm records, crop seasons, farmer questions and accountable advisory delivery across Northern Nigeria.')
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/field/jigawa-farmer.webp') }}" type="image/webp" fetchpriority="high">
@endpush
@section('content')
@if(session('status'))
    <div class="flash-message">{{ session('status') }}</div>
@endif

<section class="photo-hero">
    <figure class="photo-hero-image" data-motion="hero-media">
        <img src="{{ asset('images/field/jigawa-farmer.webp') }}" alt="A farmer examining a crop in Jigawa, northern Nigeria" width="1800" height="2047" fetchpriority="high" decoding="async" data-parallax-media>
        <figcaption><span>Jigawa, Nigeria</span><a href="https://www.pexels.com/photo/nigerian-farmer-examining-crops-in-field-34411658/">Photo by mk_photoz / Pexels</a></figcaption>
    </figure>
    <div class="photo-hero-copy" data-motion="hero-copy">
        <p class="field-kicker" data-motion-item><span></span> Crop support, connected</p>
        <h1 data-motion-item>From a farmer’s question to the next field action.</h1>
        <p data-motion-item>AgriShield gives crop programmes one accountable path from farm registration to farmer question, field review and practical advice—without losing context between calls, messages and spreadsheets.</p>
        <div class="field-actions" data-motion-item>
            <a class="field-button field-button-yellow" href="{{ route('solutions') }}">Explore the platform</a>
            <a class="field-text-link" href="{{ route('contact') }}">Plan a deployment <span>↗</span></a>
        </div>
        <dl class="photo-hero-notes" data-motion-item>
            <div><dt>For farmers</dt><dd>A familiar way to report what they see.</dd></div>
            <div><dt>For field teams</dt><dd>A clear record of what needs attention.</dd></div>
            <div><dt>For organisations</dt><dd>One view of farms, cases and follow-through.</dd></div>
        </dl>
    </div>
</section>

<section class="field-model-band" data-motion="reveal">
    <div class="field-model-copy">
        <p class="field-kicker"><span></span> Field context</p>
        <h2>See the farm without losing sight of the farmer.</h2>
        <p>Keep the field boundary, active crop and case context together while the farmer’s evidence remains clear and unobstructed.</p>
    </div>
    <div class="field-model-stage">
        <div class="field-terrain-shell" data-field-terrain aria-hidden="true">
            <div class="field-terrain-head"><span>FIELD MODEL / JIGAWA</span><b><i></i> LIVE CONTEXT</b></div>
            <canvas data-field-terrain-canvas></canvas>
            <div class="field-terrain-fallback"></div>
            <div class="field-terrain-foot"><span>12.04°N / 8.32°E</span><span>PARCEL CONTEXT</span><span>CROP / MAIZE</span></div>
        </div>
    </div>
</section>

<section class="field-promise" data-motion="reveal">
    <p>AGRISHIELD CONNECTS</p>
    <div><span>THE FARM</span><i>+</i><span>THE SEASON</span><i>+</i><span>THE QUESTION</span><i>+</i><span>THE RESPONSE</span></div>
</section>

<section class="product-story field-section">
    <div class="product-story-heading" data-motion="reveal">
        <p class="field-kicker"><span></span> One crop-support workspace</p>
        <h2>Know the field.<br>Keep the history.<br>Follow through.</h2>
    </div>
    <div class="product-story-list" data-motion="stagger">
        <article data-motion-item><span>FARMS</span><h3>Build a dependable farm register</h3><p>Keep ownership, location, boundaries and organisation access in one private record.</p></article>
        <article data-motion-item><span>CROP SEASONS</span><h3>Track what is growing now</h3><p>Connect each farm to its crop, planting period, harvest window and current season.</p></article>
        <article data-motion-item><span>FIELD VOICE</span><h3>Receive questions in the moment</h3><p>Record or upload a voice note, attach it to a farm and retain the case for review.</p></article>
        <article data-motion-item><span>CASES & ADVISORIES</span><h3>Turn evidence into accountable action</h3><p>Review crop images, publish practical advice and record whether it was acknowledged.</p></article>
    </div>
</section>

<section class="record-spine field-section">
    <header class="record-spine-intro">
        <div>
            <p class="field-kicker"><span></span> Field to follow-through</p>
            <h2>One record.<br>Five responsible handoffs.</h2>
        </div>
        <p>Every farmer question stays connected to the farm, current crop season, evidence, reviewer and response. Teams can see both the next action and the history behind it.</p>
    </header>
    <div class="record-spine-layout">
        <ol class="record-steps" aria-label="AgriShield crop support workflow">
            <li><span>01</span><div><small>FIELD TEAM</small><h3>Register the farm</h3><p>Confirm ownership, organisation access and field boundary.</p></div></li>
            <li><span>02</span><div><small>FIELD TEAM</small><h3>Open the crop season</h3><p>Record the crop and planting window that shape the case context.</p></div></li>
            <li><span>03</span><div><small>FARMER + FIELD OFFICER</small><h3>Capture the question</h3><p>Keep the voice note or crop image with the correct farm record.</p></div></li>
            <li><span>04</span><div><small>REVIEWER</small><h3>Review the evidence</h3><p>Assess what was reported and document the responsible next step.</p></div></li>
            <li><span>05</span><div><small>PROGRAMME TEAM</small><h3>Close the loop</h3><p>Publish the advisory and retain its reading and acknowledgement trail.</p></div></li>
        </ol>
        <article class="field-record-card" aria-label="Illustrative crop support record" data-motion="depth-card">
            <header><span>SAMPLE CASE / MAIZE</span><b>AWAITING REVIEW</b></header>
            <div class="field-record-context">
                <div><small>Farm</small><strong>North plot</strong></div>
                <div><small>Season</small><strong>2026 wet season</strong></div>
                <div><small>Owner</small><strong>Field team</strong></div>
            </div>
            <div class="field-record-question">
                <small>FARMER QUESTION</small>
                <blockquote>“The lower leaves are turning yellow. What should I check first?”</blockquote>
                <span>Voice note retained with case</span>
            </div>
            <div class="field-record-history">
                <div><i></i><p><small>RECEIVED</small><strong>Question linked to farm and active crop season</strong></p></div>
                <div><i></i><p><small>NEXT</small><strong>Assigned reviewer checks field evidence</strong></p></div>
                <div class="pending"><i></i><p><small>THEN</small><strong>Advisory published and acknowledgement recorded</strong></p></div>
            </div>
            <footer>Illustrative workflow · not live farmer data</footer>
        </article>
    </div>
</section>

<section class="field-documentary">
    <figure data-motion="reveal">
        <img src="{{ asset('images/field/hawul-borno-farmland.webp') }}" alt="People tending crops across farmland in Hawul, Borno State" width="1920" height="1080" loading="lazy" decoding="async" data-parallax-media>
        <figcaption>Farmland in Hawul, Borno State · <a href="https://commons.wikimedia.org/wiki/File:Farmland_Hawul_Borno_State_Nigeria._2019.DSC01973.jpg">Ifeatu Nnaobi / CC BY-SA 4.0</a></figcaption>
    </figure>
    <div data-motion="reveal">
        <p class="field-kicker"><span></span> Designed for field operations</p>
        <h2>Technology is useful when the person responsible knows what happens next.</h2>
        <p>AgriShield gives crop programmes a simple operating path: register the farm, open the crop season, capture the question, review the evidence and record the response.</p>
        <a class="field-text-link" href="{{ route('impact') }}">See how the workflow moves <span>→</span></a>
    </div>
</section>

<section class="voice-feature field-section">
    <div>
        <p class="field-kicker"><span></span> Field Voice</p>
        <h2>The field report starts with the farmer’s own words.</h2>
        <p>Field Voice securely captures a voice note and keeps it connected to the relevant farm and organisation. Transcription and translation can be enabled with a configured language provider.</p>
        <a class="field-button field-button-dark" href="{{ route('field-voice') }}">Explore Field Voice</a>
    </div>
    <div class="voice-receipt" aria-label="Example Field Voice case" data-motion="depth-card">
        <header><span>FIELD VOICE / CASE</span><b>RECEIVED</b></header>
        <div><small>QUESTION</small><p>“The lower leaves are turning yellow. What should I check first?”</p></div>
        <dl><div><dt>Farm</dt><dd>North plot</dd></div><div><dt>Crop</dt><dd>Maize</dd></div><div><dt>Status</dt><dd>Awaiting review</dd></div></dl>
        <footer>Illustrative record · not live farmer data</footer>
    </div>
</section>

<section class="deployment-standard field-section">
    <div class="deployment-standard-heading">
        <p class="field-kicker"><span></span> Deployment standard</p>
        <h2>Useful in the field.<br>Accountable at programme level.</h2>
        <p>AgriShield is designed around the operating controls a crop programme needs before automation becomes useful.</p>
    </div>
    <div class="deployment-standard-grid">
        <article><span>ACCESS</span><h3>Private organisation workspaces</h3><p>Farm, audio and crop-image records remain inside authorised organisation workflows.</p></article>
        <article><span>CONTEXT</span><h3>Every case starts with a known farm</h3><p>Questions and evidence retain the crop season and ownership context needed for review.</p></article>
        <article><span>ACCOUNTABILITY</span><h3>Advice has a visible trail</h3><p>Published actions, reading status and acknowledgement remain part of the operating record.</p></article>
        <article><span>LIMITS</span><h3>Automation is provider-aware</h3><p>Transcription, translation and specialist analysis are enabled only when a deployment configures them.</p></article>
    </div>
</section>

<section class="deployment-faq field-section">
    <header>
        <p class="field-kicker"><span></span> Before a deployment</p>
        <h2>Clear answers before field work begins.</h2>
    </header>
    <div class="deployment-faq-list">
        <details>
            <summary>Who is AgriShield built for?<span aria-hidden="true">+</span></summary>
            <p>Crop programmes, cooperatives and extension teams that need a shared record of farms, farmer questions, crop cases and follow-through.</p>
        </details>
        <details>
            <summary>Does AgriShield replace the extension officer?<span aria-hidden="true">+</span></summary>
            <p>No. The current workflow helps the responsible team retain context, review evidence and issue accountable advice. It keeps human ownership visible.</p>
        </details>
        <details>
            <summary>How does Field Voice handle local languages?<span aria-hidden="true">+</span></summary>
            <p>The platform records or uploads a farmer’s voice note first. Transcription and translation can then be enabled with a configured language provider for the deployment.</p>
        </details>
        <details>
            <summary>Can an organisation begin with one crop programme?<span aria-hidden="true">+</span></summary>
            <p>Yes. The recommended starting point is one defined crop workflow, a responsible field team and a clear support problem to evaluate.</p>
        </details>
    </div>
</section>

<section class="ledger-closing">
    <div><span>FOR CROP PROGRAMMES AND EXTENSION TEAMS</span><h2>Start with one clear field workflow.</h2></div>
    <div><p>Bring one crop programme, a defined field team and a support problem worth solving.</p><a class="field-button field-button-yellow" href="{{ route('contact') }}">Discuss a focused deployment</a></div>
</section>
@endsection
