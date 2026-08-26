<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'AgriShield AI builds trusted agricultural intelligence systems for institutions and communities across North-East Nigeria.')">
    <title>@yield('title', 'AgriShield AI Ltd')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="marketing-page credible-site">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <header @class(['site-header', 'site-header-solid', 'site-header-inverse' => ! request()->routeIs('home')]) data-header>
        <a @class(['brand', 'brand-company', 'brand-light' => ! request()->routeIs('home')]) href="{{ route('home') }}" aria-label="AgriShield AI home"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span><strong>AgriShield</strong><small>AI LTD</small></span></a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="{{ route('about') }}" @class(['active' => request()->routeIs('about')])>About</a>
            <a href="{{ route('solutions') }}" @class(['active' => request()->routeIs('solutions')])>Solutions</a>
            <a href="{{ route('impact') }}" @class(['active' => request()->routeIs('impact')])>Impact</a>
            <a href="{{ route('partners') }}" @class(['active' => request()->routeIs('partners')])>Partners</a>
            <a href="{{ route('team') }}" @class(['active' => request()->routeIs('team')])>Team</a>
        </nav>
        <div class="header-actions">
            @auth
                @if(auth()->user()->hasRole(\App\Enums\GlobalRole::PlatformAdmin->value))<a class="text-link" href="{{ route('platform.dashboard') }}">Open platform</a>
                @elseif(auth()->user()->primaryOrganizationId())<a class="text-link" href="{{ route('organization.dashboard', auth()->user()->primaryOrganizationId()) }}">Open workspace</a>@endif
            @else<a class="text-link" href="{{ route('login') }}">Sign in</a>@endauth
            <a class="button button-dark" href="{{ route('contact') }}">Request a demo</a>
        </div>
        <button class="menu-button" type="button" aria-label="Toggle menu" aria-expanded="false" data-menu-button><span></span><span></span></button>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="site-footer company-footer">
        <div><a class="brand brand-light brand-company" href="{{ route('home') }}"><span class="brand-mark"><i></i><i></i><i></i></span><span><strong>AgriShield</strong><small>AI LTD</small></span></a><p>Trusted agricultural intelligence for stronger institutions and resilient communities.</p></div>
        <div class="footer-links"><div><strong>Company</strong><a href="{{ route('about') }}">About us</a><a href="{{ route('team') }}">Our team</a><a href="{{ route('impact') }}">Impact stories</a></div><div><strong>Work with us</strong><a href="{{ route('solutions') }}">Solutions</a><a href="{{ route('partners') }}">Partnerships</a><a href="{{ route('contact') }}">Contact</a></div></div>
        <div class="footer-contact"><span>North-East Nigeria</span><a href="mailto:hello@agrishield.ai">hello@agrishield.ai</a></div>
        <small>© {{ now()->year }} AgriShield AI Ltd. All rights reserved.</small>
    </footer>
</body>
</html>
