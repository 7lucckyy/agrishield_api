@extends('layouts.marketing')
@section('title', __('website.resources.meta_title'))
@section('description', __('website.resources.meta_description'))
@section('content')
<div class="gs-inner gs-knowledge">
    <section class="gs-knowledge-hero gs-container">
        <div class="gs-knowledge-hero-copy">
            <p class="gs-context">{{ __('website.resources.eyebrow') }}</p>
            <h1>{{ __('website.resources.title') }}</h1>
            <p>{{ __('website.resources.intro') }}</p>
        </div>
        <x-field-photo src="images/field/jigawa-farmer.webp" :alt="__('website.resources.image_alt')" />
    </section>
    <section class="gs-knowledge-notes gs-container" aria-labelledby="resource-notes-title">
        <header><p class="gs-context">{{ __('website.resources.index_label') }}</p><h2 id="resource-notes-title">{{ __('website.resources.index_title') }}</h2></header>
        <div class="gs-knowledge-note-list">
            @foreach(__('website.resources.notes') as $note)
                <article><span>{{ $note['number'] }}</span><div><h3>{{ $note['title'] }}</h3><p>{{ $note['body'] }}</p></div></article>
            @endforeach
        </div>
    </section>
    <section class="gs-knowledge-aside"><div class="gs-container"><p class="gs-context">{{ __('website.resources.aside_label') }}</p><h2>{{ __('website.resources.aside_title') }}</h2><p>{{ __('website.resources.aside_body') }}</p></div></section>
    <section class="gs-next-step gs-container"><div><p class="gs-context">{{ __('website.nav.faqs') }}</p><h2>{{ __('website.resources.faq_title') }}</h2><p>{{ __('website.resources.faq_body') }}</p></div><a class="gs-link" href="{{ route('faqs') }}">{{ __('website.resources.faq_action') }} <span aria-hidden="true">↗</span></a></section>
</div>
@endsection
