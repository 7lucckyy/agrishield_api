<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', __('marketing.meta.description'))">
    <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
    <meta name="theme-color" content="#123B2A">
    <meta name="geo.region" content="NG">
    <meta name="geo.placename" content="Northern Nigeria">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32x32.png') }}" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}" sizes="180x180">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="AgriShield AI">
    <meta property="og:locale" content="{{ app()->getLocale() === 'fr' ? 'fr_FR' : (app()->getLocale() === 'ha' ? 'ha_NG' : 'en_NG') }}">
    <meta property="og:title" content="@yield('title', __('marketing.meta.title'))">
    <meta property="og:description" content="@yield('description', __('marketing.meta.description'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og/agrishield-social.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="AgriShield AI crop support in Northern Nigeria">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __('marketing.meta.title'))">
    <meta name="twitter:description" content="@yield('description', __('marketing.meta.description'))">
    <meta name="twitter:image" content="{{ asset('images/og/agrishield-social.png') }}">
    <title>@yield('title', __('marketing.meta.title'))</title>
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Organization",
            "name": "AgriShield AI",
            "url": "{{ route('home') }}",
            "logo": "{{ asset('icons/icon-512.png') }}",
            "email": "hello@agrishield.ai",
            "description": "Crop support operations for farmers and extension teams across Northern Nigeria.",
            "areaServed": {
                "@@type": "Place",
                "name": "Northern Nigeria"
            }
        }
    </script>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing-page gs-site locale-{{ app()->getLocale() }}">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header class="gs-header" data-header>
        <div class="gs-container gs-header-inner">
            <a class="gs-brand" href="{{ route('home') }}" aria-label="AgriShield AI home">
                <img src="{{ asset('brand/agrishield-mark.svg') }}" alt="" width="42" height="42">
                <span><strong>AgriShield</strong><small>NORTHERN NIGERIA</small></span>
            </a>
            <nav class="gs-nav" id="primary-navigation" aria-label="Primary navigation">
                <a href="{{ route('solutions') }}" @class(['active' => request()->routeIs('solutions')]) @if(request()->routeIs('solutions')) aria-current="page" @endif>{{ __('marketing.nav.platform') }}</a>
                <a href="{{ route('impact') }}" @class(['active' => request()->routeIs('impact')]) @if(request()->routeIs('impact')) aria-current="page" @endif>{{ __('marketing.nav.how') }}</a>
                <a href="{{ route('about') }}" @class(['active' => request()->routeIs('about', 'team')]) @if(request()->routeIs('about', 'team')) aria-current="page" @endif>{{ __('marketing.nav.about') }}</a>
            </nav>
            <div class="gs-header-actions">
                <details class="language-switcher">
                    <summary aria-label="{{ __('marketing.language.label') }}">{{ strtoupper(app()->getLocale()) }}<span aria-hidden="true">⌄</span></summary>
                    <div>
                        @foreach(config('app.supported_locales') as $locale)
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
                <a class="gs-button gs-button-primary" href="{{ route('contact') }}">{{ __('marketing.nav.plan') }}</a>
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
            <div><p>{{ __('marketing.footer.body') }}</p><a href="mailto:hello@agrishield.ai">hello@agrishield.ai</a></div>
            <div><strong>{{ __('marketing.footer.product') }}</strong><a href="{{ route('solutions') }}">{{ __('marketing.nav.platform') }}</a><a href="{{ route('field-voice') }}">{{ __('marketing.nav.field_voice') }}</a><a href="{{ route('impact') }}">{{ __('marketing.nav.how') }}</a></div>
            <div><strong>{{ __('marketing.footer.company') }}</strong><a href="{{ route('about') }}">{{ __('marketing.footer.about') }}</a><a href="{{ route('team') }}">{{ __('marketing.footer.team') }}</a><a href="{{ route('partners') }}">{{ __('marketing.footer.work') }}</a><a href="{{ route('contact') }}">{{ __('marketing.footer.contact') }}</a></div>
        </div>
        <small>© {{ now()->year }} AgriShield AI Ltd. {{ __('marketing.footer.disclaimer') }}</small>
        </div>
    </footer>
</body>
</html>
