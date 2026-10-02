@extends('layouts.marketing')
@section('title', __('marketing.pages.partners.meta_title'))
@section('description', __('marketing.pages.partners.meta_description'))
@section('content')
<div class="inner-site">
    <section class="inner-page-hero inner-page-hero-brief inner-page-hero-partners"><div data-motion="hero-copy"><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.partners.eyebrow') }}</p><h1>{{ __('marketing.pages.partners.title') }}</h1><p>{{ __('marketing.pages.partners.intro') }}</p></div><aside class="deployment-brief"><span>{{ __('marketing.pages.partners.starting_label') }}</span><ul>@foreach(array_slice(__('marketing.pages.partners.starting_points'), 0, 3) as $point)<li>{{ $point }}</li>@endforeach</ul></aside></section>
    <section class="inner-section inner-partner-grid"><div><p class="agri-kicker"><span></span>{{ __('marketing.pages.partners.starting_label') }}</p><h2>{{ __('marketing.pages.partners.starting_title') }}</h2><p>{{ __('marketing.pages.partners.starting_body') }}</p></div><ul>@foreach(__('marketing.pages.partners.starting_points') as $point)<li>{{ $point }}</li>@endforeach</ul></section>
    <section class="inner-feature-band"><div><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.partners.proof_label') }}</p><h2>{{ __('marketing.pages.partners.proof_title') }}</h2></div><p>{{ __('marketing.pages.partners.proof_body') }}</p><a class="agri-button agri-button-yellow" href="{{ route('contact') }}">{{ __('marketing.home.closing_cta') }} <span aria-hidden="true">↗</span></a></section>
</div>
@endsection
