@extends('layouts.marketing')
@section('title', __('marketing.pages.impact.meta_title'))
@section('description', __('marketing.pages.impact.meta_description'))
@section('content')
<div class="inner-site">
    <section class="inner-page-hero inner-page-hero-text inner-page-hero-impact"><div data-motion="hero-copy"><p class="agri-kicker agri-kicker-light"><span></span>{{ __('marketing.pages.impact.eyebrow') }}</p><h1>{{ __('marketing.pages.impact.title') }}</h1><p>{{ __('marketing.pages.impact.intro') }}</p></div></section>
    <section class="inner-workflow inner-section" data-motion="stagger">@foreach(__('marketing.pages.impact.steps') as $step)<article data-motion-item><span>{{ $step['number'] }}</span><div><small>{{ $step['label'] }}</small><h2>{{ $step['title'] }}</h2><p>{{ $step['body'] }}</p><b>{{ $step['note'] }}</b></div><div class="workflow-signal" aria-hidden="true"><i></i><i></i><i></i><i></i></div></article>@endforeach</section>
    @include('website.partials.cta')
</div>
@endsection
