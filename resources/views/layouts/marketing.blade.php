<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', __('marketing.meta.description'))">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <meta name="theme-color" content="#123B2A">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32x32.png') }}" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}" sizes="180x180">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AgriShield AI">
    <meta property="og:locale" content="{{ app()->getLocale() === 'fr' ? 'fr_FR' : 'en_US' }}">
    <meta property="og:title" content="@yield('title', __('marketing.meta.title'))">
    <meta property="og:description" content="@yield('description', __('marketing.meta.description'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/field/cassava-field.webp') }}">
    <meta property="og:image:width" content="1600">
    <meta property="og:image:height" content="1067">
    <meta property="og:image:alt" content="{{ __('website.a11y.social_image') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __('marketing.meta.title'))">
    <meta name="twitter:description" content="@yield('description', __('marketing.meta.description'))">
    <meta name="twitter:image" content="{{ asset('images/field/cassava-field.webp') }}">
    <title>@yield('title', __('marketing.meta.title'))</title>
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Organization",
            "name": "AgriShield AI",
            "url": "{{ route('home') }}",
            "logo": "{{ asset('icons/icon-512.png') }}",
            "email": "hello@agrishield.ai",
            "telephone": "09021135021",
            "address": {
                "@@type": "PostalAddress",
                "streetAddress": "No. J8 Kashim Shettima Plaza, Opposite UNIMAID Gate 1",
                "addressLocality": "Maiduguri",
                "addressCountry": "NG"
            },
            "description": "Agricultural intelligence that helps farmers and field teams act with confidence."
        }
    </script>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing-page gs-site locale-{{ app()->getLocale() }}">
    <a class="skip-link" href="#main-content">{{ __('website.a11y.skip') }}</a>
    <header class="gs-header" data-header>
        <div class="gs-container gs-header-inner">
            <a class="gs-brand" href="{{ route('home') }}" aria-label="{{ __('website.a11y.home') }}">
                <img src="{{ asset('brand/agrishield-mark.svg') }}" alt="" width="42" height="42">
                <span><strong>AgriShield</strong><small>AGRICULTURAL INTELLIGENCE</small></span>
            </a>
            <nav class="gs-nav" id="primary-navigation" aria-label="{{ __('website.a11y.primary_navigation') }}">
                <a href="{{ route('solutions') }}" @class(['active' => request()->routeIs('solutions')]) @if(request()->routeIs('solutions')) aria-current="page" @endif>{{ __('website.nav.solutions') }}</a>
                <details class="gs-company-menu" @if(request()->routeIs('about', 'partners')) data-current-page @endif>
                    <summary @if(request()->routeIs('about', 'partners')) aria-current="page" @endif>{{ __('website.nav.about') }}</summary>
                    <div><a href="{{ route('about') }}">{{ __('company.story') }}</a><a href="{{ route('partners') }}">{{ __('website.nav.partners') }}</a></div>
                </details>
                <details class="gs-company-menu" @if(request()->routeIs('resources', 'faqs')) data-current-page @endif>
                    <summary @if(request()->routeIs('resources', 'faqs')) aria-current="page" @endif>{{ __('website.nav.resources') }}</summary>
                    <div><a href="{{ route('resources') }}">{{ __('website.nav.resources') }}</a><a href="{{ route('faqs') }}">{{ __('website.nav.faqs') }}</a></div>
                </details>
                <a href="{{ route('careers') }}" @class(['active' => request()->routeIs('careers')]) @if(request()->routeIs('careers')) aria-current="page" @endif>{{ __('website.nav.careers') }}</a>
            </nav>
            <div class="gs-header-actions">
                <details class="language-switcher">
                    <summary aria-label="{{ __('marketing.language.label') }}">{{ strtoupper(app()->getLocale()) }}<span aria-hidden="true">⌄</span></summary>
                    <div>
                        @foreach(['en', 'fr'] as $locale)
                            <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}" hreflang="{{ $locale }}" @if(app()->getLocale() === $locale) aria-current="true" @endif>
                                <span>{{ strtoupper($locale) }}</span>{{ __('marketing.language.'.$locale) }}
                            </a>
                        @endforeach
                    </div>
                </details>
                @auth
                    @if(auth()->user()->hasRole(\App\Enums\GlobalRole::PlatformAdmin->value))
                        <a class="gs-signin" href="{{ route('platform.dashboard') }}">{{ __('marketing.nav.open_platform') }}</a>
                    @elseif(auth()->user()->primaryOrganizationId())
                        <a class="gs-signin" href="{{ route('organization.dashboard', auth()->user()->primaryOrganizationId()) }}">{{ __('marketing.nav.open_workspace') }}</a>
                    @endif
                @else
                    <a class="gs-signin" href="{{ route('login') }}">{{ __('marketing.nav.sign_in') }}</a>
                @endauth
                <a class="gs-button gs-button-primary" href="{{ route('contact') }}">{{ __('website.nav.contact') }}</a>
            </div>
            <button class="menu-button" type="button" aria-label="{{ __('marketing.nav.open_menu') }}" aria-controls="primary-navigation" aria-expanded="false" data-menu-button data-open-label="{{ __('marketing.nav.open_menu') }}" data-close-label="{{ __('marketing.nav.close_menu') }}" data-menu-label-closed="{{ __('marketing.nav.menu') }}" data-menu-label-open="{{ __('marketing.nav.close') }}">
                <span class="menu-button-label" data-menu-label>{{ __('marketing.nav.menu') }}</span>
                <span class="menu-button-lines" aria-hidden="true"><i></i><i></i></span>
            </button>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="gs-footer">
        <div class="gs-container">
        <div class="gs-footer-lead"><span>{{ strtoupper(__('marketing.footer.eyebrow')) }}</span><h2>{{ __('marketing.footer.title') }}</h2></div>
        <div class="gs-footer-grid">
            <div class="gs-footer-contact"><p>{{ __('marketing.footer.body') }}</p><strong>{{ __('website.footer.visit') }}</strong><address>{{ __('website.footer.address') }}</address><a href="tel:09021135021">09021135021</a><a href="mailto:hello@agrishield.ai">hello@agrishield.ai</a></div>
            <div><strong>{{ __('website.footer.explore') }}</strong><a href="{{ route('solutions') }}">{{ __('website.nav.solutions') }}</a><a href="{{ route('partners') }}">{{ __('website.nav.partners') }}</a></div>
            <div><strong>{{ __('website.footer.company') }}</strong><a href="{{ route('about') }}">{{ __('website.nav.about') }}</a><a href="{{ route('careers') }}">{{ __('website.nav.careers') }}</a><a href="{{ route('contact') }}">{{ __('website.nav.contact') }}</a></div>
            <div><strong>{{ __('website.footer.help') }}</strong><a href="{{ route('resources') }}">{{ __('website.nav.resources') }}</a><a href="{{ route('faqs') }}">{{ __('website.nav.faqs') }}</a></div>
        </div>
        <small>© {{ now()->year }} AgriShield AI Ltd. {{ __('marketing.footer.disclaimer') }}</small>
        </div>
    </footer>
</body>
</html>
