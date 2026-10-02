@extends('layouts.marketing')
@section('title', __('marketing.pages.solutions.meta_title'))
@section('description', __('marketing.pages.solutions.meta_description'))
@section('content')
<div class="gs-inner">
    <section class="gs-platform-hero"><div class="gs-container"><div><p class="gs-context">{{ __('marketing.pages.solutions.eyebrow') }}</p><h1>{{ __('marketing.pages.solutions.title') }}</h1><p>{{ __('marketing.pages.solutions.intro') }}</p></div><aside><span>SAT / FIELD 03</span><div><i></i><i></i><i></i><i></i></div><b>NDVI 0.72 · SIGNAL READY</b></aside></div></section>
    <section class="gs-editorial gs-container"><h2>{{ __('marketing.pages.solutions.overview_title') }}</h2><p>{{ __('marketing.pages.solutions.overview_body') }}</p></section>
    <section class="gs-feature-index gs-container">@foreach(__('marketing.pages.solutions.features') as $feature)<article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $feature['title'] }}</h2><p>{{ $feature['body'] }}</p></div><ul>@foreach($feature['items'] as $item)<li>{{ $item }}</li>@endforeach</ul></article>@endforeach</section>
    <section class="gs-provider-note"><div class="gs-container"><p class="gs-context">{{ __('marketing.pages.solutions.provider_label') }}</p><h2>{{ __('marketing.pages.solutions.provider_title') }}</h2><p>{{ __('marketing.pages.solutions.provider_body') }}</p></div></section>
    @include('website.partials.cta')
</div>
@endsection
