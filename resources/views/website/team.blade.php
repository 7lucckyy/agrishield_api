@extends('layouts.marketing')
@section('title', __('marketing.pages.team.meta_title'))
@section('description', __('marketing.pages.team.meta_description'))
@section('content')
<div class="inner-site">
    <section class="inner-page-hero inner-page-hero-team"><div data-motion="hero-copy"><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.team.eyebrow') }}</p><h1>{{ __('marketing.pages.team.title') }}</h1><p>{{ __('marketing.pages.team.intro') }}</p></div><ul class="team-disciplines">@foreach(__('marketing.pages.team.disciplines') as $discipline)<li>{{ $discipline['title'] }}</li>@endforeach</ul></section>
    <section class="inner-section inner-principles"><header class="inner-section-heading"><p class="agri-kicker"><span></span>{{ __('marketing.pages.team.disciplines_label') }}</p><h2>{{ __('marketing.pages.team.disciplines_title') }}</h2></header><div class="inner-card-grid" data-motion="stagger">@foreach(__('marketing.pages.team.disciplines') as $discipline)<article data-motion-item><h3>{{ $discipline['title'] }}</h3><p>{{ $discipline['body'] }}</p></article>@endforeach</div></section>
    @include('website.partials.cta')
</div>
@endsection
