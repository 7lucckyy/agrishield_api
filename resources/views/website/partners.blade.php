@extends('layouts.marketing')
@section('title', __('marketing.pages.partners.meta_title'))
@section('description', __('marketing.pages.partners.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-partner-hero"><div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.partners.eyebrow') }}</p><h1>{{ __('marketing.pages.partners.title') }}</h1><p>{{ __('marketing.pages.partners.intro') }}</p></div><div class="gs-partner-visual"><x-field-photo class="gs-field-photo--partner" src="images/field/leafy-harvest.webp" :alt="__('website.a11y.partners_image')" position="center" /></div></div></section>
    <section class="gs-partner-list gs-container"><header><h2>{{ __('marketing.pages.partners.starting_title') }}</h2></header><ol>@foreach(__('marketing.pages.partners.starting_points') as $point)<li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $point }}</li>@endforeach</ol></section>
    <section class="gs-responsibility"><div class="gs-container"><h2>{{ __('marketing.pages.partners.proof_title') }}</h2><div><p>{{ __('marketing.pages.partners.proof_body') }}</p><a class="gs-button gs-button-accent" href="{{ route('contact') }}">{{ __('marketing.home.closing_cta') }} ↗</a></div></div></section>
    <section class="gs-next-step gs-container"><div><p class="gs-context">{{ __('website.nav.faqs') }}</p><h2>{{ __('website.resources.faq_title') }}</h2><p>{{ __('website.resources.faq_body') }}</p></div><a class="gs-link" href="{{ route('faqs') }}">{{ __('website.resources.faq_action') }} <span aria-hidden="true">↗</span></a></section>
</div>
@endsection
