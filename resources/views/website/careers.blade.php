@extends('layouts.marketing')
@section('title', __('website.careers.meta_title'))
@section('description', __('website.careers.meta_description'))
@section('content')
<div class="gs-inner gs-careers">
    <section class="gs-careers-hero gs-container"><div><p class="gs-context">{{ __('website.careers.eyebrow') }}</p><h1>{{ __('website.careers.title') }}</h1><p>{{ __('website.careers.intro') }}</p></div><x-field-photo src="images/field/corn-field.webp" :alt="__('website.careers.image_alt')" /></section>
    <section class="gs-careers-values"><div class="gs-container"><header><p class="gs-context">{{ __('website.careers.culture_label') }}</p><h2>{{ __('website.careers.culture_title') }}</h2></header><div class="gs-careers-values-list">@foreach(__('website.careers.principles') as $principle)<article><span>{{ $principle['number'] }}</span><h3>{{ $principle['title'] }}</h3><p>{{ $principle['body'] }}</p></article>@endforeach</div></div></section>
    <section class="gs-careers-openings gs-container"><div><p class="gs-context">{{ __('website.careers.openings_label') }}</p><h2>{{ __('website.careers.openings_title') }}</h2></div><div><p>{{ __('website.careers.openings_body') }}</p><a class="gs-button gs-button-primary" href="mailto:hello@agrishield.ai?subject={{ rawurlencode(__('website.careers.openings_subject')) }}">{{ __('website.careers.openings_action') }} <span aria-hidden="true">↗</span></a></div></section>
</div>
@endsection
