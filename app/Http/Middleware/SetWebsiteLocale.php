<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetWebsiteLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', ['en']);
        $requestedLocale = $request->query('lang');

        if (is_string($requestedLocale) && in_array($requestedLocale, $supportedLocales, true)) {
            $request->session()->put('website_locale', $requestedLocale);
        }

        $locale = $request->session()->get('website_locale')
            ?? $request->getPreferredLanguage($supportedLocales)
            ?? config('app.locale', 'en');

        App::setLocale($locale);

        return $next($request);
    }
}
