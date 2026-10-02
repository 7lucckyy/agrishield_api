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

<div class="agri-home">
    <section class="agri-hero">
        <div class="agri-hero-copy" data-motion="hero-copy">
            <p class="agri-kicker" data-motion-item><span></span>{{ __('marketing.home.eyebrow') }}</p>
            <h1 data-motion-item>{{ __('marketing.home.headline') }}</h1>
            <p class="agri-hero-lede" data-motion-item>{{ __('marketing.home.lede') }}</p>
            <div class="agri-actions" data-motion-item>
                <a class="agri-button agri-button-primary" href="{{ route('contact') }}">{{ __('marketing.home.primary_cta') }}<span aria-hidden="true">↗</span></a>
                <a class="agri-text-link" href="{{ route('solutions') }}">{{ __('marketing.home.secondary_cta') }}<span aria-hidden="true">→</span></a>
            </div>
        </div>

        <figure class="agri-hero-photo" data-motion="hero-media">
            <img src="{{ asset('images/field/jigawa-farmer.webp') }}" alt="{{ __('marketing.home.hero_image_alt') }}" width="1920" height="1080" fetchpriority="high" decoding="async">
            <figcaption>{{ __('marketing.home.hero_caption') }}</figcaption>
        </figure>
    </section>

    <section class="agri-field-model agri-section">
        <div class="agri-field-model-copy" data-motion="reveal">
            <p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.home.field_plan') }}</p>
            <h2>{{ __('marketing.home.field_model_title') }}</h2>
            <p>{{ __('marketing.home.field_model_body') }}</p>
        </div>
        <div data-motion="reveal">
            <article class="farm-plan farm-plan-standalone" aria-label="{{ __('marketing.home.field_plan') }}">
                <header>
                    <div><span>{{ __('marketing.home.field_plan') }}</span><strong>{{ __('marketing.home.field_name') }}</strong></div>
                    <small><i></i>{{ __('marketing.home.context_ready') }}</small>
                </header>
                <div class="farm-plan-body">
                    <svg class="farm-plan-map" viewBox="0 0 620 320" role="img" aria-label="{{ __('marketing.home.allocated') }}">
                        <defs><pattern id="field-grid" width="34" height="34" patternUnits="userSpaceOnUse"><path d="M34 0H0V34" fill="none" stroke="currentColor" stroke-opacity=".1"/></pattern></defs>
                        <rect width="620" height="320" fill="url(#field-grid)"/>
                        <path d="M68 68 245 31l111 72-20 163-219 18-64-106Z" class="plot plot-a"/>
                        <path d="m245 31 111 72 180-29 47 106-58 105-189-19 20-163Z" class="plot plot-b"/>
                        <path d="m73 178 64 106 199-18 189 19 58-105-31 116-397 4Z" class="plot plot-c"/>
                        <path d="M68 68 245 31l111 72 180-29 47 106-31 116-397 4-82-122Z" class="farm-outline"/>
                        <circle cx="410" cy="132" r="8" class="farm-marker"/><circle cx="410" cy="132" r="17" class="farm-marker-ring"/>
                    </svg>
                    <div class="farm-plan-sections">
                        @foreach(__('marketing.home.sections') as $index => $section)
                            <div><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $section['name'] }}</strong><small>{{ $section['crop'] }} · {{ $section['area'] }}</small></div>
                        @endforeach
                    </div>
                </div>
                <footer><span></span>{{ __('marketing.home.allocated') }}</footer>
            </article>
        </div>
    </section>

    <section class="agri-audiences agri-section">
        <header class="agri-section-head" data-motion="reveal">
            <div><p class="agri-kicker"><span></span>{{ __('marketing.home.audience_eyebrow') }}</p><h2>{{ __('marketing.home.audience_title') }}</h2></div>
            <p>{{ __('marketing.home.audience_intro') }}</p>
        </header>
        <div class="audience-grid" data-motion="stagger">
            @foreach(__('marketing.home.audiences') as $index => $audience)
                <article class="audience-card audience-card-{{ $index + 1 }}" data-motion-item>
                    <header><small>{{ $audience['label'] }}</small></header>
                    <div><h3>{{ $audience['title'] }}</h3><p>{{ $audience['body'] }}</p></div>
                    <ul>@foreach($audience['items'] as $item)<li><span aria-hidden="true">✓</span>{{ $item }}</li>@endforeach</ul>
                </article>
            @endforeach
        </div>
    </section>

    <section class="agri-capabilities agri-section">
        <header class="agri-section-head" data-motion="reveal">
            <div><p class="agri-kicker"><span></span>{{ __('marketing.home.capabilities_eyebrow') }}</p><h2>{{ __('marketing.home.capabilities_title') }}</h2></div>
            <p>{{ __('marketing.home.capabilities_intro') }}</p>
        </header>
        <div class="capability-atlas" data-motion="stagger">
            @foreach(__('marketing.home.capabilities') as $feature)
                <article class="capability-module capability-module-{{ $feature['visual'] }}" data-motion-item>
                    <header><span>{{ $feature['code'] }}</span><i aria-hidden="true"></i></header>
                    <div class="capability-instrument" aria-hidden="true">
                        @switch($feature['visual'])
                            @case('satellite')
                                <div class="satellite-tile"><i></i><i></i><i></i><i></i><span>NDVI</span></div>
                                <div class="signal-trend"><i></i><i></i><i></i><i></i><i></i><i></i></div>
                                @break
                            @case('soil')
                                <div class="soil-gauge"><span style="--level:31%"></span><i></i></div>
                                <div class="soil-readings"><i>pH 6.4</i><i>N 42</i><i>K 18</i></div>
                                @break
                            @case('diagnosis')
                                <div class="diagnosis-view"><span></span><i></i><b>IMG 024</b></div>
                                <div class="diagnosis-lines"><i></i><i></i><i></i></div>
                                @break
                            @case('voice')
                                <div class="field-wave"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                                <div class="voice-route-mini"><span>HA</span><i>→</i><span>TEXT</span><i>→</i><span>ACTION</span></div>
                                @break
                            @case('finance')
                                <div class="finance-sheet"><span>₦</span><i></i><i></i><i></i><b>FARM LINKED</b></div>
                                @break
                            @case('weather')
                                <div class="rain-bars"><i></i><i></i><i></i><i></i><i></i><i></i></div>
                                <div class="weather-axis"><span>NOW</span><span>+24H</span></div>
                                @break
                        @endswitch
                    </div>
                    <div class="capability-reading"><strong>{{ $feature['value'] }}</strong><small>{{ $feature['label'] }}</small></div>
                    <div class="capability-copy"><h3>{{ $feature['title'] }}</h3><p>{{ $feature['body'] }}</p></div>
                </article>
            @endforeach
        </div>
        <p class="capability-atlas-note">{{ __('marketing.home.capabilities_note') }}</p>
    </section>

    <section class="agri-flow agri-section">
        <div class="agri-flow-intro" data-motion="reveal">
            <p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.home.flow_eyebrow') }}</p>
            <h2>{{ __('marketing.home.flow_title') }}</h2>
            <p>{{ __('marketing.home.flow_intro') }}</p>
        </div>
        <ol class="agri-flow-steps" data-motion="stagger">
            @foreach(__('marketing.home.steps') as $step)
                <li data-motion-item><span>{{ $step['number'] }}</span><div><small>{{ $step['label'] }}</small><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></div></li>
            @endforeach
        </ol>
    </section>

    <section class="agri-product agri-section">
        <header class="agri-section-head" data-motion="reveal">
            <div><p class="agri-kicker"><span></span>{{ __('marketing.home.product_eyebrow') }}</p><h2>{{ __('marketing.home.product_title') }}</h2></div>
            <p>{{ __('marketing.home.product_body') }}</p>
        </header>
        <div class="product-window" data-motion="depth-card">
            <header class="product-window-bar">
                <div class="product-window-brand"><img src="{{ asset('brand/agrishield-mark.svg') }}" alt="" width="30" height="30"><span><strong>AgriShield</strong><small>{{ __('marketing.home.dashboard.title') }}</small></span></div>
                <span>{{ __('marketing.home.dashboard.season') }}</span><i aria-hidden="true"></i>
            </header>
            <div class="product-window-body">
                <aside aria-hidden="true"><i class="active"></i><i></i><i></i><i></i></aside>
                <div class="product-window-content">
                    <div class="product-window-title"><div><small>{{ __('marketing.home.dashboard.status') }}</small><h3>{{ __('marketing.home.dashboard.farm') }}</h3></div><span>{{ __('marketing.home.dashboard.sections') }}</span></div>
                    <div class="product-metrics">
                        @foreach(__('marketing.home.sections') as $section)
                            <div><small>{{ $section['name'] }}</small><strong>{{ $section['crop'] }}</strong><span>{{ $section['area'] }}</span></div>
                        @endforeach
                    </div>
                    <div class="product-workspace">
                        <div class="product-map" aria-hidden="true">
                            <svg viewBox="0 0 620 320"><path d="M68 68 245 31l111 72-20 163-219 18-64-106Z"/><path d="m245 31 111 72 180-29 47 106-58 105-189-19 20-163Z"/><path d="m73 178 64 106 199-18 189 19 58-105-31 116-397 4Z"/><circle cx="410" cy="132" r="9"/></svg>
                            <span>{{ __('marketing.home.dashboard.rain') }}</span>
                        </div>
                        <article class="product-alert">
                            <small>{{ __('marketing.home.dashboard.attention') }}</small><h4>{{ __('marketing.home.dashboard.alert_title') }}</h4><p>{{ __('marketing.home.dashboard.alert_body') }}</p><b>{{ __('marketing.home.dashboard.action') }} <span aria-hidden="true">→</span></b>
                        </article>
                    </div>
                </div>
            </div>
            <footer>{{ __('marketing.home.dashboard.note') }}</footer>
        </div>
    </section>

    <section class="agri-field-story">
        <figure data-motion="reveal">
            <img src="{{ asset('images/field/hawul-borno-farmland.webp') }}" alt="{{ __('marketing.home.field_image_alt') }}" width="1800" height="2047" loading="lazy" decoding="async">
            <figcaption>{{ __('marketing.home.field_image_caption') }}</figcaption>
        </figure>
        <div class="agri-field-copy" data-motion="reveal">
            <p class="agri-kicker"><span></span>{{ __('marketing.home.field_eyebrow') }}</p><h2>{{ __('marketing.home.field_title') }}</h2><p>{{ __('marketing.home.field_body') }}</p>
            <dl>@foreach(__('marketing.home.field_points') as $point)<div><dt>{{ $point['title'] }}</dt><dd>{{ $point['body'] }}</dd></div>@endforeach</dl>
        </div>
    </section>

    <section class="agri-languages agri-section">
        <div class="agri-language-copy" data-motion="reveal">
            <p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.home.language_eyebrow') }}</p><h2>{{ __('marketing.home.language_title') }}</h2><p>{{ __('marketing.home.language_body') }}</p>
        </div>
        <div class="language-cards" data-motion="stagger">
            @foreach(__('marketing.home.language_cards') as $language)
                <a href="{{ request()->fullUrlWithQuery(['lang' => strtolower($language['code'])]) }}" @class(['active' => app()->getLocale() === strtolower($language['code'])]) data-motion-item>
                    <span>{{ $language['code'] }}</span><strong>{{ $language['name'] }}</strong><p>{{ $language['sample'] }}</p><i aria-hidden="true">→</i>
                </a>
            @endforeach
        </div>
    </section>

    <section class="agri-faq agri-section">
        <header><p class="agri-kicker"><span></span>{{ __('marketing.home.faq_eyebrow') }}</p><h2>{{ __('marketing.home.faq_title') }}</h2></header>
        <div class="agri-faq-list">
            @foreach(__('marketing.home.faqs') as $faq)
                <details><summary>{{ $faq['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $faq['answer'] }}</p></details>
            @endforeach
        </div>
    </section>

    <section class="agri-closing">
        <div><span>{{ strtoupper(__('marketing.home.closing_label')) }}</span><h2>{{ __('marketing.home.closing_title') }}</h2></div>
        <div><p>{{ __('marketing.home.closing_body') }}</p><a class="agri-button agri-button-yellow" href="{{ route('contact') }}">{{ __('marketing.home.closing_cta') }}<span aria-hidden="true">↗</span></a></div>
    </section>
</div>
@endsection
