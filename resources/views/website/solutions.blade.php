@extends('layouts.marketing')
@section('title', __('marketing.pages.solutions.meta_title'))
@section('description', __('marketing.pages.solutions.meta_description'))
@section('content')
<div class="inner-site">
    <section class="inner-page-hero inner-page-hero-platform"><div data-motion="hero-copy"><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.solutions.eyebrow') }}</p><h1>{{ __('marketing.pages.solutions.title') }}</h1><p>{{ __('marketing.pages.solutions.intro') }}</p></div><div class="inner-hero-instrument instrument-satellite" data-motion="hero-media" aria-hidden="true"><span>SAT / FIELD 03</span><div><i></i><i></i><i></i><i></i></div><b>NDVI 0.72 · SIGNAL READY</b></div></section>
    <section class="inner-section inner-platform-intro"><div><p class="agri-kicker"><span></span>{{ __('marketing.pages.solutions.overview_label') }}</p><h2>{{ __('marketing.pages.solutions.overview_title') }}</h2></div><p>{{ __('marketing.pages.solutions.overview_body') }}</p></section>
    <section class="inner-feature-catalog" data-motion="stagger">@foreach(__('marketing.pages.solutions.features') as $feature)<article data-motion-item><span>{{ Str::afterLast($feature['code'], ' / ') }}</span><div><h2>{{ $feature['title'] }}</h2><p>{{ $feature['body'] }}</p></div><ul>@foreach($feature['items'] as $item)<li>{{ $item }}</li>@endforeach</ul></article>@endforeach</section>
    <section class="inner-feature-band"><div><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.solutions.provider_label') }}</p><h2>{{ __('marketing.pages.solutions.provider_title') }}</h2></div><p>{{ __('marketing.pages.solutions.provider_body') }}</p></section>
    @include('website.partials.cta')
</div>
@endsection
