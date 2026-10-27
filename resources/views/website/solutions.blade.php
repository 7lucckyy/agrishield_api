@extends('layouts.marketing')
@section('title', __('marketing.pages.solutions.meta_title'))
@section('description', __('marketing.pages.solutions.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-platform-hero"><div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.solutions.eyebrow') }}</p><h1>{{ __('marketing.pages.solutions.title') }}</h1><p>{{ __('marketing.pages.solutions.intro') }}</p></div><div class="gs-platform-visual"><x-field-photo class="gs-field-photo--platform" src="images/field/african-fields-aerial.webp" :alt="__('website.a11y.solutions_image')" position="center" /></div></div></section>
    <section class="gs-editorial gs-container"><h2>{{ __('marketing.pages.solutions.overview_title') }}</h2><p>{{ __('marketing.pages.solutions.overview_body') }}</p></section>
    <section class="gs-feature-index gs-container">@foreach(__('marketing.home.public_capabilities') as $feature)<article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $feature['title'] }}</h2><p>{{ $feature['body'] }}</p></div></article>@endforeach</section>
    <section class="gs-provider-note"><div class="gs-container"><p class="gs-context">{{ __('marketing.pages.solutions.provider_label') }}</p><h2>{{ __('marketing.pages.solutions.provider_title') }}</h2><p>{{ __('marketing.pages.solutions.provider_body') }}</p></div></section>
    <section class="gs-next-step gs-container"><div><p class="gs-context">{{ __('website.nav.resources') }}</p><h2>{{ __('website.resources.index_title') }}</h2><p>{{ __('website.resources.intro') }}</p></div><a class="gs-link" href="{{ route('resources') }}">{{ __('website.nav.resources') }} <span aria-hidden="true">↗</span></a></section>
    @include('website.partials.cta')
</div>
@endsection
