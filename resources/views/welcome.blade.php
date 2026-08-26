@extends('layouts.marketing')
@section('title', 'AgriShield AI Ltd | Agricultural Intelligence for North-East Nigeria')
@section('content')
@if(session('status'))<div class="flash-message">{{ session('status') }}</div>@endif

<section class="operations-hero">
    <div class="operations-hero-copy">
        <p class="section-label">Agricultural intelligence for resilient communities</p>
        <h1>Evidence for the field.<br>Clarity for decisions.</h1>
        <p class="hero-lede">AgriShield AI helps governments, development partners and agricultural organizations turn farmer, farm, livestock, climate and market data into coordinated action.</p>
        <div class="hero-actions"><a class="button button-primary" href="{{ route('contact') }}">Request a demonstration</a><a class="text-action" href="{{ route('solutions') }}">Explore our solutions <span>→</span></a></div>
    </div>
    <aside class="deployment-brief" aria-label="AgriShield AI deployment brief">
        <div class="brief-heading"><span>Deployment brief</span><small>North-East Nigeria</small></div>
        <dl><div><dt>Who we serve</dt><dd>Public institutions, development partners and producer networks</dd></div><div><dt>Evidence connected</dt><dd>People, farms, livestock, land, climate and markets</dd></div><div><dt>Delivery model</dt><dd>Field registration through institutional reporting</dd></div><div><dt>Regional focus</dt><dd>Borno, Adamawa, Yobe, Bauchi, Gombe and Taraba</dd></div></dl>
    </aside>
</section>

<section class="service-bar" aria-label="Platform strengths"><span>Secure registries</span><span>Geospatial evidence</span><span>Field-ready delivery</span><span>Programme accountability</span></section>

<section class="plain-intro section-pad">
    <div><p class="section-label">About AgriShield AI</p><h2>Technology grounded in local reality.</h2></div>
    <div><p class="intro-statement">We build the trusted digital infrastructure North-East Nigeria’s agricultural future deserves.</p><p>Our work connects responsible AI, geospatial science, field knowledge and public programme delivery—so intelligence reaches the people responsible for acting on it.</p><a class="text-action" href="{{ route('about') }}">How we work <span>→</span></a></div>
</section>

<section class="solution-index section-pad" id="solutions">
    <div class="section-intro"><p class="section-label">Connected solutions</p><h2>Systems that work together.</h2><p>Begin with a focused deployment and grow into statewide agricultural infrastructure.</p></div>
    <div class="solution-rows">
        <a href="{{ route('solutions') }}#registries"><div><small>Identity and evidence</small><strong>Farmer census and digital registries</strong></div><p>Verified, georeferenced profiles for accountable service delivery.</p><span>View solutions →</span></a>
        <a href="{{ route('solutions') }}#land"><div><small>Land and production</small><strong>GIS, soil and crop intelligence</strong></div><p>Farm boundaries, satellite signals and land evidence in one decision layer.</p><span>View solutions →</span></a>
        <a href="{{ route('solutions') }}#risk"><div><small>Risk and resilience</small><strong>Food security and climate monitoring</strong></div><p>Earlier visibility into production gaps, drought, floods and emerging needs.</p><span>View solutions →</span></a>
        <a href="{{ route('solutions') }}#markets"><div><small>Delivery and opportunity</small><strong>Finance, mechanisation and markets</strong></div><p>Connect verified production to practical services and stronger routes to market.</p><span>View solutions →</span></a>
    </div>
    <a class="text-action catalog-link" href="{{ route('solutions') }}">Explore all twelve solution areas <span>→</span></a>
</section>

<section class="delivery-section section-pad">
    <div class="section-intro"><p class="section-label">Delivery model</p><h2>A practical route from evidence to outcomes.</h2><p>We connect the operational pieces that often sit apart.</p></div>
    <ol class="delivery-list"><li><span>01</span><div><strong>Organize producers</strong><p>Build trusted clusters around geography, value chain and production cycle.</p></div></li><li><span>02</span><div><strong>Verify and map</strong><p>Capture farmers, farms, livestock and assets with field-level evidence.</p></div></li><li><span>03</span><div><strong>Coordinate services</strong><p>Connect intelligence to advisory, finance, inputs and mechanisation.</p></div></li><li><span>04</span><div><strong>Measure outcomes</strong><p>Track delivery, learn from the field and connect producers to markets.</p></div></li></ol>
</section>

<section class="regional-section section-pad">
    <div><p class="section-label">Geographic focus</p><h2>Built for North-East Nigeria.</h2><p>Serving governments, development partners and farming communities across the region.</p><a class="button button-primary" href="{{ route('contact') }}">Discuss a regional deployment</a></div>
    <div class="state-directory" aria-label="States in regional focus"><div><span>01</span>Borno</div><div><span>02</span>Adamawa</div><div><span>03</span>Yobe</div><div><span>04</span>Bauchi</div><div><span>05</span>Gombe</div><div><span>06</span>Taraba</div></div>
</section>

<section class="working-principles section-pad"><div class="section-intro"><p class="section-label">Why AgriShield AI</p><h2>Trust is part of the system.</h2></div><div class="principle-rows"><article><h3>Listen before building</h3><p>Start with institutions, communities and the decisions the system must improve.</p></article><article><h3>Design for the last mile</h3><p>Offline resilience, clear interfaces and practical support are part of the architecture.</p></article><article><h3>Make trust visible</h3><p>Privacy, validation, auditability and responsible AI belong in every workflow.</p></article><article><h3>Measure what matters</h3><p>Connect technology outputs to programme outcomes and community-level impact.</p></article></div></section>

<section class="approach-section section-pad"><div class="section-intro"><p class="section-label">Impact approaches</p><h2>What better evidence makes possible.</h2></div><div class="approach-list"><a href="{{ route('impact') }}"><small>Digital public infrastructure</small><strong>A unified farmer registry for evidence-led planning</strong><span>North-East Nigeria →</span></a><a href="{{ route('impact') }}"><small>Livestock intelligence</small><strong>Mapping corridors for safer, smarter services</strong><span>Borno and Yobe →</span></a><a href="{{ route('impact') }}"><small>Climate resilience</small><strong>Turning early climate signals into field action</strong><span>Adamawa and Taraba →</span></a></div></section>

<section class="contact-block"><div><p class="section-label">Work with AgriShield AI</p><h2>Bring us the hard agricultural problem.</h2></div><div><p>For project scoping, demonstrations, partnerships or institutional enquiries, our team is ready to listen.</p><a class="button button-light" href="{{ route('contact') }}">Start a conversation</a></div></section>
@endsection
