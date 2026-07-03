<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserPreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = config('yelgolf.locales', []);
        $localeCookieName = config('yelgolf.locale_cookie', 'yelgolf_locale');
        $defaultLocale = config('app.locale', 'en');
        $isAuthenticated = $request->hasSession() && (bool) $request->session()->get('admin_authenticated');

        $requestedLocale = $isAuthenticated
            ? (string) $request->session()->get('locale', $request->cookie($localeCookieName, $defaultLocale))
            : $defaultLocale;

        if (! array_key_exists($requestedLocale, $availableLocales)) {
            $requestedLocale = $defaultLocale;
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $requestedLocale);
        }

        app()->setLocale($requestedLocale);

        return $next($request);
    }
}