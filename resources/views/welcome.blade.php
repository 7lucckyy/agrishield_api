@extends('layouts.marketing')
@section('title', __('marketing.meta.title'))
@section('description', __('marketing.meta.description'))
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/field/cassava-field.webp') }}" type="image/webp" fetchpriority="high">
@endpush

@section('content')
@if(session('status'))
    <div class="flash-message">{{ session('status') }}</div>
@endif

<div class="gs-home">
    <section class="gs-hero">
        <figure class="gs-hero-image" data-motion="hero-media">
            <img src="{{ asset('images/field/cassava-field.webp') }}" alt="{{ __('marketing.home.hero_image_alt') }}" width="1600" height="1067" fetchpriority="high" decoding="async">
        </figure>
        <div class="gs-container gs-hero-inner">
            <div class="gs-hero-copy" data-motion="hero-copy">
                <p class="gs-context" data-motion-item>{{ __('marketing.home.eyebrow') }}</p>
                <h1 data-motion-item>{{ __('marketing.home.headline') }}</h1>
                <p class="gs-lede" data-motion-item>{{ __('marketing.home.lede') }}</p>
                <div class="gs-actions" data-motion-item>
                    <a class="gs-button gs-button-accent" href="{{ route('contact') }}">{{ __('marketing.home.primary_cta') }} <span aria-hidden="true">↗</span></a>
                    <a class="gs-link" href="{{ route('solutions') }}">{{ __('marketing.home.secondary_cta') }} <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </div>
    </section>

    <section class="gs-conviction" aria-labelledby="conviction-title">
        <div class="gs-container gs-conviction-grid">
            <div><p class="gs-context">{{ __('marketing.home.conviction_label') }}</p><h2 id="conviction-title">{{ __('marketing.home.conviction_title') }}</h2></div>
            <div class="gs-conviction-aside"><p>{{ __('marketing.home.conviction_body') }}</p><a class="gs-link" href="{{ route('solutions') }}">{{ __('marketing.home.secondary_cta') }} <span aria-hidden="true">↗</span></a></div>
        </div>
    </section>

    <section class="gs-section gs-container gs-audiences">
        <header class="gs-section-head"><h2>{{ __('marketing.home.audience_title') }}</h2><p>{{ __('marketing.home.audience_intro') }}</p></header>
        <div class="gs-audience-list">
            @foreach(__('marketing.home.audiences') as $audience)
                <article><p class="gs-context">{{ $audience['label'] }}</p><div><h3>{{ $audience['title'] }}</h3><p>{{ $audience['body'] }}</p></div></article>
            @endforeach
        </div>
    </section>

    <section class="gs-section gs-capabilities">
        <div class="gs-container">
            <header class="gs-section-head"><h2>{{ __('marketing.home.public_capabilities_title') }}</h2><p>{{ __('marketing.home.public_capabilities_intro') }}</p></header>
            <div class="gs-capability-list">
                @foreach(__('marketing.home.public_capabilities') as $feature)
                    <article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $feature['title'] }}</h3><p>{{ $feature['body'] }}</p></div></article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="gs-field-story">
        <figure><img src="{{ asset('images/field/jos-farmers.webp') }}" alt="{{ __('marketing.home.field_image_alt') }}" width="1400" height="933" loading="lazy" decoding="async"></figure>
        <div><p class="gs-context">{{ __('marketing.home.field_eyebrow') }}</p><h2>{{ __('marketing.home.field_title') }}</h2><p>{{ __('marketing.home.field_body') }}</p></div>
    </section>

    <section class="gs-explore gs-container" aria-labelledby="explore-title">
        <header><p class="gs-context">{{ __('website.home.explore_label') }}</p><h2 id="explore-title">{{ __('website.home.explore_title') }}</h2><p>{{ __('website.home.explore_body') }}</p></header>
        <div><a href="{{ route('solutions') }}">{{ __('website.nav.solutions') }} <span aria-hidden="true">↗</span></a><a href="{{ route('about') }}">{{ __('website.nav.about') }} <span aria-hidden="true">↗</span></a><a href="{{ route('resources') }}">{{ __('website.nav.resources') }} <span aria-hidden="true">↗</span></a><a href="{{ route('contact') }}">{{ __('website.nav.contact') }} <span aria-hidden="true">↗</span></a></div>
    </section>

    <section class="gs-closing"><div class="gs-container gs-closing-grid"><div><p class="gs-context">{{ __('marketing.home.closing_label') }}</p><h2>{{ __('marketing.home.closing_title') }}</h2></div><div><p>{{ __('marketing.home.closing_body') }}</p><a class="gs-button gs-button-accent" href="{{ route('contact') }}">{{ __('marketing.home.closing_cta') }} <span aria-hidden="true">↗</span></a></div></div></section>
</div>
@endsection
