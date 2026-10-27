@extends('layouts.marketing')
@section('title', __('marketing.pages.about.meta_title'))
@section('description', __('marketing.pages.about.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-about-hero gs-container">
        <div class="gs-about-heading">
            <p class="gs-context">{{ __('marketing.pages.about.eyebrow') }}</p>
            <h1>{{ __('marketing.pages.about.title') }}</h1>
            <p>{{ __('marketing.pages.about.intro') }}</p>
        </div>
        <div class="gs-about-visual">
            <x-field-photo src="images/field/jos-planting.webp" :alt="__('website.a11y.about_image')" position="center" />
        </div>
    </section>
    <nav class="gs-company-index gs-container" aria-label="{{ __('company.navigation') }}">
        @foreach(['purpose', 'values', 'change'] as $section)
            <a href="#{{ $section }}">{{ __('company.'.$section.'.label') }} <span aria-hidden="true">↗</span></a>
        @endforeach
    </nav>
    <section class="gs-purpose gs-container gs-section" id="purpose" aria-labelledby="purpose-title">
        <header><p class="gs-context">{{ __('company.purpose.label') }}</p><h2 id="purpose-title">{{ __('company.purpose.title') }}</h2></header>
        <div class="gs-purpose-grid">
            @foreach(['mission', 'vision'] as $purpose)
                <article><span>{{ $loop->iteration === 1 ? '01' : '02' }}</span><h3>{{ __('company.purpose.'.$purpose) }}</h3><p>{{ __('company.purpose.'.$purpose.'_body') }}</p></article>
            @endforeach
        </div>
    </section>
    <section class="gs-principles" id="values" aria-labelledby="about-principles">
        <div class="gs-container">
            <header><p class="gs-context">{{ __('company.values.label') }}</p><h2 id="about-principles">{{ __('company.values.title') }}</h2></header>
            <div>@foreach(__('marketing.pages.about.principles') as $principle)<article><span>{{ $principle['code'] }}</span><h3>{{ $principle['title'] }}</h3><p>{{ $principle['body'] }}</p></article>@endforeach</div>
        </div>
    </section>
    <section class="gs-change" id="change" aria-labelledby="change-title">
        <div class="gs-container gs-section">
            <header class="gs-section-head"><div><p class="gs-context">{{ __('company.change.label') }}</p><h2 id="change-title">{{ __('company.change.title') }}</h2></div><p>{{ __('company.change.intro') }}</p></header>
            <ol class="gs-change-path">@foreach(__('company.change.steps') as $step)<li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><small>{{ $step['label'] }}</small><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></li>@endforeach</ol>
            <p class="gs-change-note">{{ __('company.change.note') }}</p>
        </div>
    </section>
    <section class="gs-team-note">
        <div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.about.team_label') }}</p><h2>{{ __('marketing.pages.about.team_title') }}</h2></div><div><p>{{ __('marketing.pages.about.team_body') }}</p><a class="gs-link" href="{{ route('careers') }}">{{ __('website.nav.careers') }} <span aria-hidden="true">↗</span></a></div></div>
    </section>
    @include('website.partials.cta')
</div>
@endsection
