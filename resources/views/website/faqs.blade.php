@extends('layouts.marketing')
@section('title', __('website.faqs.meta_title'))
@section('description', __('website.faqs.meta_description'))
@section('content')
<div class="gs-inner gs-faq-page">
    <section class="gs-faq-hero gs-container"><p class="gs-context">{{ __('website.faqs.eyebrow') }}</p><h1>{{ __('website.faqs.title') }}</h1><p>{{ __('website.faqs.intro') }}</p></section>
    <div class="gs-faq-groups gs-container">
        @foreach(__('website.faqs.groups') as $group)
            <section class="gs-faq-group" aria-labelledby="faq-group-{{ $loop->index }}"><h2 id="faq-group-{{ $loop->index }}">{{ $group['title'] }}</h2><div>@foreach($group['questions'] as $question)<details><summary>{{ $question['question'] }}<span aria-hidden="true">+</span></summary><p>{{ $question['answer'] }}</p></details>@endforeach</div></section>
        @endforeach
    </div>
    <section class="gs-next-step gs-container"><div><p class="gs-context">{{ __('website.nav.contact') }}</p><h2>{{ __('website.faqs.more_title') }}</h2><p>{{ __('website.faqs.more_body') }}</p></div><a class="gs-link" href="{{ route('contact') }}">{{ __('website.faqs.more_action') }} <span aria-hidden="true">↗</span></a></section>
</div>
@endsection
