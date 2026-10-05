@extends('layouts.marketing')
@section('title', __('marketing.pages.solutions.meta_title'))
@section('description', __('marketing.pages.solutions.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-platform-hero"><div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.solutions.eyebrow') }}</p><h1>{{ __('marketing.pages.solutions.title') }}</h1><p>{{ __('marketing.pages.solutions.intro') }}</p></div><div class="gs-platform-visual"><x-field-photo class="gs-field-photo--platform" src="images/field/commons/kano-farmer-tending-field.webp" alt="A farmer tending a green field beneath a broad sky in Kano State" location="Kano State · field work" credit="Photobyamin" source="https://commons.wikimedia.org/wiki/File:Farmer_tending_to_his_farm.jpg" position="lower" /><aside><span>SAT / FIELD 03</span><div><i></i><i></i><i></i><i></i></div><b>NDVI 0.72 · SIGNAL READY</b></aside></div></div></section>
    <section class="gs-editorial gs-container"><h2>{{ __('marketing.pages.solutions.overview_title') }}</h2><p>{{ __('marketing.pages.solutions.overview_body') }}</p></section>
    <section class="gs-feature-index gs-container">@foreach(__('marketing.pages.solutions.features') as $feature)<article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $feature['title'] }}</h2><p>{{ $feature['body'] }}</p></div><ul>@foreach($feature['items'] as $item)<li>{{ $item }}</li>@endforeach</ul></article>@endforeach</section>
    <section class="gs-reality"><div class="gs-container"><header><p class="gs-context">{{ __('marketing.pages.solutions.reality_label') }}</p><h2>{{ __('marketing.pages.solutions.reality_title') }}</h2><p>{{ __('marketing.pages.solutions.reality_body') }}</p></header><div>@foreach(__('marketing.pages.solutions.reality_layers') as $layer)<article><span>{{ $layer['status'] }}</span><h3>{{ $layer['title'] }}</h3><p>{{ $layer['body'] }}</p></article>@endforeach</div></div></section>
    <section class="gs-provider-note"><div class="gs-container"><p class="gs-context">{{ __('marketing.pages.solutions.provider_label') }}</p><h2>{{ __('marketing.pages.solutions.provider_title') }}</h2><p>{{ __('marketing.pages.solutions.provider_body') }}</p></div></section>
    @include('website.partials.cta')
</div>
@endsection
