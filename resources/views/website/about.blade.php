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
            <x-field-photo src="images/field/commons/kano-farmer-tending-field.webp" alt="A farmer tends a green rice field in Kano State" location="Kano State · field work" credit="Photobyamin" source="https://commons.wikimedia.org/wiki/File:Farmer_tending_to_his_farm.jpg" position="right" />
            <div class="gs-about-visual-note" aria-hidden="true"><span>{{ __('marketing.pages.about.principles.0.code') }}</span><strong>{{ __('marketing.pages.about.principles.0.title') }}</strong></div>
        </div>
    </section>
    <section class="gs-editorial gs-container" aria-labelledby="about-statement">
        <div><p class="gs-context">{{ __('marketing.pages.about.statement_label') }}</p><h2 id="about-statement">{{ __('marketing.pages.about.statement_title') }}</h2></div>
        <div>@foreach(__('marketing.pages.about.statement_body') as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div>
    </section>
    <section class="gs-principles" aria-labelledby="about-principles">
        <div class="gs-container">
            <header><p class="gs-context">{{ __('marketing.pages.about.principles_label') }}</p><h2 id="about-principles">{{ __('marketing.pages.about.principles_title') }}</h2></header>
            <div>@foreach(__('marketing.pages.about.principles') as $principle)<article><span>{{ $principle['code'] }}</span><h3>{{ $principle['title'] }}</h3><p>{{ $principle['body'] }}</p></article>@endforeach</div>
        </div>
    </section>
    <section class="gs-team-note">
        <div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.about.team_label') }}</p><h2>{{ __('marketing.pages.about.team_title') }}</h2></div><div><p>{{ __('marketing.pages.about.team_body') }}</p><a class="gs-link" href="{{ route('partners') }}">{{ __('marketing.footer.work') }} <span aria-hidden="true">↗</span></a></div></div>
    </section>
    @include('website.partials.cta')
</div>
@endsection