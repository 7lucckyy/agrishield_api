@extends('layouts.marketing')
@section('title', __('marketing.meta.title'))
@section('description', __('marketing.meta.description'))
@push('head')
    <link rel="preload" as="image" href="{{ asset('images/field/jigawa-farmer.webp') }}" type="image/webp" fetchpriority="high">
@endpush

@section('content')
@if(session('status'))
    <div class="flash-message">{{ session('status') }}</div>
@endif

<div class="gs-home">
    <section class="gs-hero gs-container">
        <div class="gs-hero-copy" data-motion="hero-copy">
            <p class="gs-context" data-motion-item>{{ __('marketing.home.eyebrow') }} · Northern Nigeria</p>
            <h1 data-motion-item>{{ __('marketing.home.headline') }}</h1>
            <p class="gs-lede" data-motion-item>{{ __('marketing.home.lede') }}</p>
            <div class="gs-actions" data-motion-item>
                <a class="gs-button gs-button-primary" href="{{ route('contact') }}">{{ __('marketing.home.primary_cta') }} <span aria-hidden="true">↗</span></a>
                <a class="gs-link" href="{{ route('solutions') }}">{{ __('marketing.home.secondary_cta') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
        <figure class="gs-hero-image" data-motion="hero-media">
            <img src="{{ asset('images/field/jigawa-farmer.webp') }}" alt="{{ __('marketing.home.hero_image_alt') }}" width="1920" height="1080" fetchpriority="high" decoding="async">
            <figcaption>{{ __('marketing.home.hero_caption') }}</figcaption>
        </figure>
    </section>

    <section class="gs-record">
        <div class="gs-container gs-record-grid">
            <div class="gs-record-intro">
                <p class="gs-context">{{ __('marketing.home.field_plan') }}</p>
                <h2>{{ __('marketing.home.field_model_title') }}</h2>
                <p>{{ __('marketing.home.field_model_body') }}</p>
            </div>
            <article class="gs-farm-record" aria-label="{{ __('marketing.home.field_plan') }}">
                <header><div><small>{{ __('marketing.home.field_plan') }}</small><strong>{{ __('marketing.home.field_name') }}</strong></div><span><i></i>{{ __('marketing.home.context_ready') }}</span></header>
                <div class="gs-farm-body">
                    <svg viewBox="0 0 620 320" role="img" aria-label="{{ __('marketing.home.allocated') }}">
                        <defs><pattern id="gs-field-grid" width="34" height="34" patternUnits="userSpaceOnUse"><path d="M34 0H0V34" fill="none" stroke="currentColor" stroke-opacity=".1"/></pattern></defs>
                        <rect width="620" height="320" fill="url(#gs-field-grid)"/>
                        <path d="M68 68 245 31l111 72-20 163-219 18-64-106Z" class="plot-a"/>
                        <path d="m245 31 111 72 180-29 47 106-58 105-189-19 20-163Z" class="plot-b"/>
                        <path d="m73 178 64 106 199-18 189 19 58-105-31 116-397 4Z" class="plot-c"/>
                        <path d="M68 68 245 31l111 72 180-29 47 106-31 116-397 4-82-122Z" class="outline"/>
                        <circle cx="410" cy="132" r="7" class="marker"/>
                    </svg>
                    <div class="gs-farm-sections">
                        @foreach(__('marketing.home.sections') as $index => $section)
                            <div><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><p><strong>{{ $section['name'] }}</strong><small>{{ $section['crop'] }} · {{ $section['area'] }}</small></p></div>
                        @endforeach
                    </div>
                </div>
                <footer>{{ __('marketing.home.allocated') }} <span>{{ __('marketing.home.dashboard.note') }}</span></footer>
            </article>
        </div>
    </section>

    <section class="gs-section gs-container gs-audiences">
        <header class="gs-section-head"><h2>{{ __('marketing.home.audience_title') }}</h2><p>{{ __('marketing.home.audience_intro') }}</p></header>
        <div class="gs-audience-list">
            @foreach(__('marketing.home.audiences') as $audience)
                <article><p class="gs-context">{{ $audience['label'] }}</p><div><h3>{{ $audience['title'] }}</h3><p>{{ $audience['body'] }}</p></div><ul>@foreach($audience['items'] as $item)<li>{{ $item }}</li>@endforeach</ul></article>
            @endforeach
        </div>
    </section>

    <section class="gs-section gs-capabilities">
        <div class="gs-container">
            <header class="gs-section-head"><h2>{{ __('marketing.home.capabilities_title') }}</h2><p>{{ __('marketing.home.capabilities_intro') }}</p></header>
            <div class="gs-capability-list">
                @foreach(__('marketing.home.capabilities') as $feature)
                    <article><span>{{ $feature['code'] }}</span><div><h3>{{ $feature['title'] }}</h3><p>{{ $feature['body'] }}</p></div><p class="gs-reading"><strong>{{ $feature['value'] }}</strong><small>{{ $feature['label'] }}</small></p></article>
                @endforeach
            </div>
            <p class="gs-note">{{ __('marketing.home.capabilities_note') }}</p>
        </div>
    </section>

    <section class="gs-workflow">
        <div class="gs-container gs-workflow-grid">
            <div><p class="gs-context">{{ __('marketing.home.flow_eyebrow') }}</p><h2>{{ __('marketing.home.flow_title') }}</h2><p>{{ __('marketing.home.flow_intro') }}</p></div>
            <ol>@foreach(__('marketing.home.steps') as $step)<li><span>{{ $step['number'] }}</span><div><small>{{ $step['label'] }}</small><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></div></li>@endforeach</ol>
        </div>
    </section>

    <section class="gs-section gs-container gs-product">
        <header class="gs-section-head"><h2>{{ __('marketing.home.product_title') }}</h2><p>{{ __('marketing.home.product_body') }}</p></header>
        <div class="gs-product-frame">
            <header><div><img src="{{ asset('brand/agrishield-mark.svg') }}" alt="" width="28" height="28"><strong>{{ __('marketing.home.dashboard.farm') }}</strong></div><span>{{ __('marketing.home.dashboard.season') }}</span></header>
            <div class="gs-product-body">
                <div class="gs-product-map"><svg viewBox="0 0 620 320" aria-hidden="true"><path d="M68 68 245 31l111 72-20 163-219 18-64-106Z"/><path d="m245 31 111 72 180-29 47 106-58 105-189-19 20-163Z"/><path d="m73 178 64 106 199-18 189 19 58-105-31 116-397 4Z"/><circle cx="410" cy="132" r="9"/></svg><span>{{ __('marketing.home.dashboard.rain') }}</span></div>
                <div class="gs-product-summary"><small>{{ __('marketing.home.dashboard.attention') }}</small><h3>{{ __('marketing.home.dashboard.alert_title') }}</h3><p>{{ __('marketing.home.dashboard.alert_body') }}</p><a href="{{ route('impact') }}">{{ __('marketing.home.dashboard.action') }} →</a></div>
            </div>
            <footer>{{ __('marketing.home.dashboard.note') }}</footer>
        </div>
    </section>

    <section class="gs-field-story">
        <figure><img src="{{ asset('images/field/hawul-borno-farmland.webp') }}" alt="{{ __('marketing.home.field_image_alt') }}" width="1800" height="2047" loading="lazy" decoding="async"><figcaption>{{ __('marketing.home.field_image_caption') }}</figcaption></figure>
        <div><p class="gs-context">{{ __('marketing.home.field_eyebrow') }}</p><h2>{{ __('marketing.home.field_title') }}</h2><p>{{ __('marketing.home.field_body') }}</p><dl>@foreach(__('marketing.home.field_points') as $point)<div><dt>{{ $point['title'] }}</dt><dd>{{ $point['body'] }}</dd></div>@endforeach</dl></div>
    </section>

    <section class="gs-section gs-container gs-language">
        <div><h2>{{ __('marketing.home.language_title') }}</h2><p>{{ __('marketing.home.language_body') }}</p></div>
        <nav aria-label="{{ __('marketing.language.label') }}">@foreach(__('marketing.home.language_cards') as $language)<a href="{{ request()->fullUrlWithQuery(['lang' => strtolower($language['code'])]) }}" @class(['active' => app()->getLocale() === strtolower($language['code'])])><span>{{ $language['code'] }}</span><strong>{{ $language['name'] }}</strong><small>{{ $language['sample'] }}</small></a>@endforeach</nav>
    </section>

    <section class="gs-section gs-container gs-faq">
        <header><h2>{{ __('marketing.home.faq_title') }}</h2></header>
        <div>@foreach(__('marketing.home.faqs') as $faq)<details><summary>{{ $faq['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $faq['answer'] }}</p></details>@endforeach</div>
    </section>

    <section class="gs-closing"><div class="gs-container gs-closing-grid"><div><p class="gs-context">{{ __('marketing.home.closing_label') }}</p><h2>{{ __('marketing.home.closing_title') }}</h2></div><div><p>{{ __('marketing.home.closing_body') }}</p><a class="gs-button gs-button-accent" href="{{ route('contact') }}">{{ __('marketing.home.closing_cta') }} <span aria-hidden="true">↗</span></a></div></div></section>
</div>
@endsection
