@extends('layouts.marketing')
@section('title', 'Platform | AgriShield AI')
@section('description', 'Explore AgriShield’s connected workspace for farms, crop seasons, farmer questions, crop cases and advisories.')
@section('content')
<section class="inner-hero">
    <p class="eyebrow light"><span></span> The AgriShield platform</p>
    <h1>One crop workflow.<br>Nothing important lost.</h1>
    <p>Give field teams a shared place to organise farms, understand the current season and follow farmer questions through to action.</p>
</section>

<section class="platform-catalog section-pad">
    <article><span>REGISTER</span><div><h2>Farm and organisation records</h2><p>Keep farms, boundaries, ownership and team access together in a secure workspace.</p></div><ul><li>Farm profiles</li><li>Validated boundaries</li><li>Organisation roles</li></ul></article>
    <article><span>TRACK</span><div><h2>Crop seasons</h2><p>Record what is planted and retain the dates and context needed for ongoing support.</p></div><ul><li>Crop catalogue</li><li>Planting and harvest dates</li><li>Active season history</li></ul></article>
    <article><span>LISTEN</span><div><h2>Field Voice</h2><p>Receive a farmer’s voice note, connect it to a permitted farm and keep the case visible to the responsible team.</p></div><ul><li>Browser recording</li><li>Private audio</li><li>Case history</li></ul></article>
    <article><span>REVIEW</span><div><h2>Crop cases</h2><p>Store field observations and crop images while the team reviews what needs to happen next.</p></div><ul><li>Protected image intake</li><li>Review status</li><li>Case notes</li></ul></article>
    <article><span>ACT</span><div><h2>Advisories</h2><p>Publish a practical farm-level action and retain a record of reading and acknowledgement.</p></div><ul><li>Recommended actions</li><li>Priority and timing</li><li>Acknowledgement history</li></ul></article>
    <article><span>OPERATE</span><div><h2>Team oversight</h2><p>Give organisation and platform administrators a clear view of farms, users and operations.</p></div><ul><li>Organisation overview</li><li>Member management</li><li>Operational health</li></ul></article>
</section>

<section class="provider-note field-section">
    <div><p class="field-kicker"><span></span> Extend when ready</p><h2>Connect specialist services without rebuilding the workflow.</h2></div>
    <div><p>AgriShield can connect language, crop-analysis and earth-observation providers when a deployment has selected and configured them.</p><p>The core workspace remains useful on its own: farms, crop seasons, private field evidence, cases and advisories stay together.</p></div>
</section>
@include('website.partials.cta')
@endsection
