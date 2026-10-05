@extends('layouts.marketing')
@section('title', __('marketing.pages.partners.meta_title'))
@section('description', __('marketing.pages.partners.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-partner-hero"><div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.partners.eyebrow') }}</p><h1>{{ __('marketing.pages.partners.title') }}</h1><p>{{ __('marketing.pages.partners.intro') }}</p></div><div class="gs-partner-visual"><x-field-photo class="gs-field-photo--partner" src="images/field/commons/bauchi-solar-rice.webp" alt="Solar-powered irrigation equipment operating across rice fields in Bauchi State" location="Bauchi State · solar-powered irrigation" credit="SMMIMAGES" source="https://commons.wikimedia.org/wiki/File:SOLAR_POWER_IRRIGATION_RICE_FARMING.jpg" position="lower" /><aside><strong>{{ __('marketing.pages.partners.starting_label') }}</strong><p>{{ __('marketing.pages.partners.starting_body') }}</p></aside></div></div></section>
    <section class="gs-partner-list gs-container"><header><h2>{{ __('marketing.pages.partners.starting_title') }}</h2></header><ol>@foreach(__('marketing.pages.partners.starting_points') as $point)<li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $point }}</li>@endforeach</ol></section>
    <section class="gs-responsibility"><div class="gs-container"><h2>{{ __('marketing.pages.partners.proof_title') }}</h2><div><p>{{ __('marketing.pages.partners.proof_body') }}</p><a class="gs-button gs-button-accent" href="{{ route('contact') }}">{{ __('marketing.home.closing_cta') }} ↗</a></div></div></section>
</div>
@endsection
