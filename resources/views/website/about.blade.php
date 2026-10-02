@extends('layouts.marketing')
@section('title', __('marketing.pages.about.meta_title'))
@section('description', __('marketing.pages.about.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-about-hero gs-container">
        <div><p class="gs-context">Northern Nigeria · About AgriShield</p><h1>{{ __('marketing.pages.about.title') }}</h1><p>{{ __('marketing.pages.about.intro') }}</p></div>
        <figure><img src="{{ asset('images/field/hawul-borno-farmland.webp') }}" alt="{{ __('marketing.home.field_image_alt') }}" width="1800" height="2047"><figcaption>{{ __('marketing.home.field_image_caption') }}</figcaption></figure>
    </section>
    <section class="gs-editorial gs-container"><h2>{{ __('marketing.pages.about.statement_title') }}</h2><div>@foreach(__('marketing.pages.about.statement_body') as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div></section>
    <section class="gs-principles"><div class="gs-container"><header><h2>{{ __('marketing.pages.about.principles_title') }}</h2></header><div>@foreach(__('marketing.pages.about.principles') as $principle)<article><span>{{ $principle['code'] }}</span><h3>{{ $principle['title'] }}</h3><p>{{ $principle['body'] }}</p></article>@endforeach</div></div></section>
    <section class="gs-team-note"><div class="gs-container"><h2>{{ __('marketing.pages.about.team_title') }}</h2><div><p>{{ __('marketing.pages.about.team_body') }}</p><a class="gs-link" href="{{ route('team') }}">{{ __('marketing.footer.team') }} →</a></div></div></section>
    @include('website.partials.cta')
</div>
@endsection
