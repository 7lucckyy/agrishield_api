@extends('layouts.marketing')
@section('title', __('marketing.pages.impact.meta_title'))
@section('description', __('marketing.pages.impact.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-text-hero"><div class="gs-container"><p class="gs-context">{{ __('marketing.pages.impact.eyebrow') }}</p><h1>{{ __('marketing.pages.impact.title') }}</h1><p>{{ __('marketing.pages.impact.intro') }}</p></div></section>
    <section class="gs-process gs-container">@foreach(__('marketing.pages.impact.steps') as $step)<article><span>{{ $step['number'] }}</span><div><small>{{ $step['label'] }}</small><h2>{{ $step['title'] }}</h2><p>{{ $step['body'] }}</p></div><b>{{ $step['note'] }}</b></article>@endforeach</section>
    @include('website.partials.cta')
</div>
@endsection
