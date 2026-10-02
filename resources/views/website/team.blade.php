@extends('layouts.marketing')
@section('title', __('marketing.pages.team.meta_title'))
@section('description', __('marketing.pages.team.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-team-hero"><div class="gs-container"><p class="gs-context">{{ __('marketing.pages.team.eyebrow') }}</p><h1>{{ __('marketing.pages.team.title') }}</h1><p>{{ __('marketing.pages.team.intro') }}</p></div></section>
    <section class="gs-discipline gs-container"><header><h2>{{ __('marketing.pages.team.disciplines_title') }}</h2></header><div>@foreach(__('marketing.pages.team.disciplines') as $discipline)<article><span>{{ $discipline['code'] }}</span><h3>{{ $discipline['title'] }}</h3><p>{{ $discipline['body'] }}</p></article>@endforeach</div></section>
    @include('website.partials.cta')
</div>
@endsection
