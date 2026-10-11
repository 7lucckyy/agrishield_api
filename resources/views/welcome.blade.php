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
            <dl class="gs-hero-proof" data-motion-item>
                @foreach(__('marketing.home.hero_proof') as $proof)
                    <div><dt>{{ $proof['label'] }}</dt><dd>{{ $proof['value'] }}</dd></div>
                @endforeach
            </dl>
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
            <p class="gs-note"><strong>{{ __('marketing.home.capabilities_note_label') }}</strong> {{ __('marketing.home.capabilities_note') }}</p>
        </div>
    </section>

    <section class="gs-workflow" id="workflow" aria-labelledby="workflow-title">
        <div class="gs-container gs-workflow-grid">
            <div><p class="gs-context">{{ __('marketing.home.flow_eyebrow') }}</p><h2 id="workflow-title">{{ __('marketing.home.flow_title') }}</h2><p>{{ __('marketing.home.flow_intro') }}</p></div>
            <ol>@foreach(__('marketing.home.steps') as $step)<li><span>{{ $step['number'] }}</span><div><small>{{ $step['label'] }}</small><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></div></li>@endforeach</ol>
        </div>
    </section>

    <section class="gs-section gs-container gs-product" data-product-showcase>
        <header class="gs-section-head"><h2>{{ __('marketing.home.product_title') }}</h2><p>{{ __('marketing.home.product_body') }}</p></header>
        <div class="gs-product-frame gs-product-desk">
            <header class="gs-product-bar">
                <div><img src="{{ asset('brand/agrishield-mark.svg') }}" alt="" width="28" height="28"><strong>{{ __('marketing.home.dashboard.farm') }}</strong></div>
                <span><i aria-hidden="true"></i>{{ __('marketing.home.dashboard.status') }}</span>
                <small>{{ __('marketing.home.dashboard.season') }}</small>
            </header>
            <div class="gs-product-workspace">
                <div class="gs-product-rail" role="tablist" aria-label="{{ __('marketing.home.product_title') }}">
                    @foreach([0, 2, 3, 4] as $showcaseIndex)
                        @php($feature = __('marketing.home.capabilities.'.$showcaseIndex))
                        <button id="product-tab-{{ $showcaseIndex }}" type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="product-panel-{{ $showcaseIndex }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-product-tab="{{ $showcaseIndex }}">
                            <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span><small>{{ $feature['code'] }}</small><strong>{{ $feature['title'] }}</strong></span>
                        </button>
                    @endforeach
                </div>
                <div class="gs-product-stage">
                    <section id="product-panel-0" role="tabpanel" aria-labelledby="product-tab-0" data-product-panel="0">
                        <div class="gs-product-map">
                            <svg viewBox="0 0 620 320" role="img" aria-label="{{ __('marketing.home.allocated') }}"><path d="M68 68 245 31l111 72-20 163-219 18-64-106Z"/><path d="m245 31 111 72 180-29 47 106-58 105-189-19 20-163Z"/><path d="m73 178 64 106 199-18 189 19 58-105-31 116-397 4Z"/><circle cx="410" cy="132" r="9"/></svg>
                            <span>{{ __('marketing.home.dashboard.rain') }}</span>
                        </div>
                        <aside class="gs-product-summary"><small>{{ __('marketing.home.dashboard.attention') }}</small><h3>{{ __('marketing.home.dashboard.alert_title') }}</h3><p>{{ __('marketing.home.dashboard.alert_body') }}</p><a href="#workflow">{{ __('marketing.home.dashboard.action') }} →</a></aside>
                    </section>
                    <section id="product-panel-2" role="tabpanel" aria-labelledby="product-tab-2" data-product-panel="2" hidden>
                        <div class="gs-case-media"><img src="{{ asset('images/field/commons/kaduna-tomato-farm.webp') }}" alt="" width="1900" height="1200" loading="lazy"><span>{{ __('marketing.home.capabilities.2.code') }}</span></div>
                        <aside class="gs-product-summary"><small>{{ __('marketing.home.capabilities.2.label') }}</small><h3>{{ __('marketing.home.capabilities.2.value') }}</h3><p>{{ __('marketing.home.capabilities.2.body') }}</p><div class="gs-confidence"><span></span><span></span><span></span><span></span><i></i></div></aside>
                    </section>
                    <section id="product-panel-3" role="tabpanel" aria-labelledby="product-tab-3" data-product-panel="3" hidden>
                        <div class="gs-voice-surface">
                            <span class="gs-voice-time">{{ __('marketing.home.capabilities.3.value') }}</span>
                            <div class="gs-waveform" aria-hidden="true">@foreach([22, 46, 32, 74, 54, 88, 42, 68, 36, 80, 52, 30, 64, 44, 72, 28, 58, 40] as $height)<i style="--wave-height: {{ $height }}%"></i>@endforeach</div>
                            <strong>{{ __('marketing.home.capabilities.3.label') }}</strong>
                        </div>
                        <aside class="gs-product-summary"><small>{{ __('marketing.home.capabilities.3.code') }}</small><h3>{{ __('marketing.home.capabilities.3.title') }}</h3><p>{{ __('marketing.home.capabilities.3.body') }}</p><span class="gs-review-state">{{ __('marketing.home.dashboard.status') }}</span></aside>
                    </section>
                    <section id="product-panel-4" role="tabpanel" aria-labelledby="product-tab-4" data-product-panel="4" hidden>
                        <div class="gs-finance-surface">
                            <div><small>{{ __('marketing.home.dashboard.farm') }}</small><strong>{{ __('marketing.home.capabilities.4.value') }}</strong></div>
                            <ol>
                                <li><span>01</span><strong>{{ __('marketing.home.sections.0.crop') }}</strong><small>{{ __('marketing.home.sections.0.area') }}</small></li>
                                <li><span>02</span><strong>{{ __('marketing.home.sections.1.crop') }}</strong><small>{{ __('marketing.home.sections.1.area') }}</small></li>
                                <li><span>03</span><strong>{{ __('marketing.home.sections.2.crop') }}</strong><small>{{ __('marketing.home.sections.2.area') }}</small></li>
                            </ol>
                        </div>
                        <aside class="gs-product-summary"><small>{{ __('marketing.home.capabilities.4.code') }}</small><h3>{{ __('marketing.home.capabilities.4.title') }}</h3><p>{{ __('marketing.home.capabilities.4.body') }}</p><span class="gs-review-state">{{ __('marketing.home.capabilities.4.label') }}</span></aside>
                    </section>
                </div>
            </div>
            <footer>{{ __('marketing.home.dashboard.note') }}</footer>
        </div>
    </section>

    <section class="gs-readiness" id="product-reality" aria-labelledby="product-reality-title">
        <div class="gs-container">
            <header class="gs-readiness-head">
                <div>
                    <p class="gs-context">{{ __('marketing.home.readiness.eyebrow') }}</p>
                    <h2 id="product-reality-title">{{ __('marketing.home.readiness.title') }}</h2>
                </div>
                <p>{{ __('marketing.home.readiness.intro') }}</p>
            </header>

            <ol class="gs-transaction-path" aria-label="{{ __('marketing.home.readiness.path_label') }}">
                @foreach(__('marketing.home.readiness.path') as $step)
                    <li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $step }}</strong></li>
                @endforeach
            </ol>

            <div class="gs-reality-desk">
                <article class="gs-market-window">
                    <header><span>{{ __('marketing.home.readiness.market.code') }}</span><b>{{ __('marketing.home.readiness.market.verified') }}</b></header>
                    <div>
                        <small>{{ __('marketing.home.readiness.market.partner') }}</small>
                        <h3>{{ __('marketing.home.readiness.market.title') }}</h3>
                        <p>{{ __('marketing.home.readiness.market.body') }}</p>
                        <dl>
                            @foreach(__('marketing.home.readiness.market.facts') as $fact)
                                <div><dt>{{ $fact['label'] }}</dt><dd>{{ $fact['value'] }}</dd></div>
                            @endforeach
                        </dl>
                        <div class="gs-capacity"><span style="--capacity: 68%"></span></div>
                        <small>{{ __('marketing.home.readiness.market.capacity') }}</small>
                    </div>
                    <footer><span>{{ __('marketing.home.readiness.market.action') }}</span><b>{{ __('marketing.home.readiness.market.status') }}</b></footer>
                </article>

                <article class="gs-money-book">
                    <header><span>{{ __('marketing.home.readiness.ledger.code') }}</span><b>{{ __('marketing.home.readiness.ledger.voice') }}</b></header>
                    <div class="gs-profit-reading">
                        <small>{{ __('marketing.home.readiness.ledger.profit_label') }}</small>
                        <strong>{{ __('marketing.home.readiness.ledger.profit') }}</strong>
                        <span>{{ __('marketing.home.readiness.ledger.margin') }}</span>
                    </div>
                    <div class="gs-ledger-lines">
                        @foreach(__('marketing.home.readiness.ledger.entries') as $entry)
                            <div><span>{{ $entry['type'] }}</span><p><strong>{{ $entry['title'] }}</strong><small>{{ $entry['source'] }}</small></p><b>{{ $entry['amount'] }}</b></div>
                        @endforeach
                    </div>
                    <footer>{{ __('marketing.home.readiness.ledger.notice') }}</footer>
                </article>

                <aside class="gs-operations-proof">
                    <section>
                        <header><span>{{ __('marketing.home.readiness.alert.code') }}</span><b>{{ __('marketing.home.readiness.alert.severity') }}</b></header>
                        <h3>{{ __('marketing.home.readiness.alert.title') }}</h3>
                        <p>{{ __('marketing.home.readiness.alert.body') }}</p>
                        <ul>@foreach(__('marketing.home.readiness.alert.channels') as $channel)<li>{{ $channel }}</li>@endforeach</ul>
                    </section>
                    <section>
                        <header><span>{{ __('marketing.home.readiness.operations.code') }}</span><b>{{ __('marketing.home.readiness.operations.status') }}</b></header>
                        <h3>{{ __('marketing.home.readiness.operations.title') }}</h3>
                        <ol>@foreach(__('marketing.home.readiness.operations.checks') as $check)<li><i aria-hidden="true"></i>{{ $check }}</li>@endforeach</ol>
                    </section>
                </aside>
            </div>
            <p class="gs-readiness-note"><strong>{{ __('marketing.home.readiness.note_label') }}</strong> {{ __('marketing.home.readiness.note') }}</p>
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
