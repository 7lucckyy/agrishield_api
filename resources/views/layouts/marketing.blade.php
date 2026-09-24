<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'AgriShield connects crop farmers, field evidence and extension teams across Northern Nigeria.')">
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
    <meta property="og:locale" content="en_NG">
    <meta property="og:title" content="@yield('title', 'AgriShield AI')">
    <meta property="og:description" content="@yield('description', 'Crop support operations for farmers and extension teams across Northern Nigeria.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og/agrishield-social.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="AgriShield AI crop support in Northern Nigeria">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'AgriShield AI')">
    <meta name="twitter:description" content="@yield('description', 'Crop support operations for farmers and extension teams across Northern Nigeria.')">
    <meta name="twitter:image" content="{{ asset('images/og/agrishield-social.png') }}">
    <title>@yield('title', 'AgriShield AI')</title>
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
<body class="marketing-page credible-site field-site">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header class="field-header" data-header>
        <div class="field-header-inner">
            <a class="field-brand" href="{{ route('home') }}" aria-label="AgriShield AI home">
                <img src="{{ asset('brand/agrishield-mark.svg') }}" alt="" width="42" height="42">
                <span><strong>AgriShield</strong><small>NORTHERN NIGERIA</small></span>
            </a>
            <nav class="field-nav" id="primary-navigation" aria-label="Primary navigation">
                <span class="field-menu-eyebrow">Explore AgriShield</span>
                <a href="{{ route('solutions') }}" @class(['active' => request()->routeIs('solutions')]) @if(request()->routeIs('solutions')) aria-current="page" @endif>Platform</a>
                <a href="{{ route('field-voice') }}" @class(['active' => request()->routeIs('field-voice')]) @if(request()->routeIs('field-voice')) aria-current="page" @endif>Field Voice</a>
                <a href="{{ route('impact') }}" @class(['active' => request()->routeIs('impact')]) @if(request()->routeIs('impact')) aria-current="page" @endif>How it works</a>
                <a href="{{ route('about') }}" @class(['active' => request()->routeIs('about', 'team', 'partners')]) @if(request()->routeIs('about', 'team', 'partners')) aria-current="page" @endif>Company</a>
            </nav>
            <div class="field-header-actions">
                @auth
                    @if(auth()->user()->hasRole(\App\Enums\GlobalRole::PlatformAdmin->value))
                        <a class="field-signin" href="{{ route('platform.dashboard') }}">Open platform</a>
                    @elseif(auth()->user()->primaryOrganizationId())
                        <a class="field-signin" href="{{ route('organization.dashboard', auth()->user()->primaryOrganizationId()) }}">Open workspace</a>
                    @endif
                @else
                    <a class="field-signin" href="{{ route('login') }}">Sign in</a>
                @endauth
                <a class="field-button field-button-dark" href="{{ route('contact') }}">Plan a deployment</a>
            </div>
            <button class="menu-button" type="button" aria-label="Open main menu" aria-controls="primary-navigation" aria-expanded="false" data-menu-button>
                <span class="menu-button-label" data-menu-label>Menu</span>
                <span class="menu-button-lines" aria-hidden="true"><i></i><i></i></span>
            </button>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="field-footer">
        <div class="field-footer-lead"><span>AGRISHIELD / NORTHERN NIGERIA</span><h2>One record from field question to follow-through.</h2></div>
        <div class="field-footer-grid">
            <div><p>Secure crop records, farmer questions and accountable advisory delivery for field teams.</p><a href="mailto:hello@agrishield.ai">hello@agrishield.ai</a></div>
            <div><strong>Product</strong><a href="{{ route('solutions') }}">Platform</a><a href="{{ route('field-voice') }}">Field Voice</a><a href="{{ route('impact') }}">How it works</a></div>
            <div><strong>Company</strong><a href="{{ route('about') }}">About</a><a href="{{ route('partners') }}">Work with us</a><a href="{{ route('contact') }}">Contact</a></div>
        </div>
        <small>© {{ now()->year }} AgriShield AI Ltd. Crop guidance should be reviewed by qualified local professionals.</small>
    </footer>
</body>
</html>
