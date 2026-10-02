@extends('layouts.marketing')
@section('title', __('marketing.pages.about.meta_title'))
@section('description', __('marketing.pages.about.meta_description'))
@section('content')
<div class="inner-site">
    <section class="inner-page-hero inner-page-hero-documentary inner-page-hero-about">
        <div data-motion="hero-copy"><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.about.eyebrow') }}</p><h1>{{ __('marketing.pages.about.title') }}</h1><p>{{ __('marketing.pages.about.intro') }}</p></div>
        <figure class="inner-hero-documentary" data-motion="hero-media"><img src="{{ asset('images/field/hawul-borno-farmland.webp') }}" alt="{{ __('marketing.home.field_image_alt') }}" width="1800" height="2047"><figcaption>{{ __('marketing.home.field_image_caption') }}</figcaption></figure>
    </section>
    <section class="inner-statement inner-section" data-motion="reveal"><div><p class="agri-kicker"><span></span>{{ __('marketing.pages.about.statement_label') }}</p><h2>{{ __('marketing.pages.about.statement_title') }}</h2></div><div>@foreach(__('marketing.pages.about.statement_body') as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div></section>
    <section class="inner-section inner-principles"><header class="inner-section-heading"><p class="agri-kicker"><span></span>{{ __('marketing.pages.about.principles_label') }}</p><h2>{{ __('marketing.pages.about.principles_title') }}</h2></header><div class="inner-card-grid" data-motion="stagger">@foreach(__('marketing.pages.about.principles') as $principle)<article data-motion-item><span>{{ $principle['code'] }}</span><h3>{{ $principle['title'] }}</h3><p>{{ $principle['body'] }}</p></article>@endforeach</div></section>
    <section class="inner-feature-band"><div><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.about.team_label') }}</p><h2>{{ __('marketing.pages.about.team_title') }}</h2></div><p>{{ __('marketing.pages.about.team_body') }}</p><a class="agri-text-link agri-text-link-light" href="{{ route('team') }}">{{ __('marketing.footer.team') }} <span aria-hidden="true">→</span></a></section>
    @include('website.partials.cta')
</div>
@endsection
