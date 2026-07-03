<?php

namespace App\Providers;

use App\Services\CurrentPlayerResolver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function (ViewContract $view): void {
            $availableThemes = config('yelgolf.themes', []);
            $themeCookieName = config('yelgolf.theme_cookie', 'yelgolf_theme');
            $defaultTheme = config('yelgolf.default_theme', 'dark');
            $request = request();
            $isAdminAuthenticated = $request->hasSession() && (bool) $request->session()->get('admin_authenticated');
            $requestedTheme = $isAdminAuthenticated
                ? ($request->hasSession()
                    ? (string) $request->session()->get('theme', $request->cookie($themeCookieName, $defaultTheme))
                    : (string) $request->cookie($themeCookieName, $defaultTheme))
                : $defaultTheme;
            $currentPlayer = app(CurrentPlayerResolver::class)->resolve($request);

            $view->with('availableLocales', config('yelgolf.locales', []));
            $view->with('availableThemes', $availableThemes);
            $view->with('currentTheme', array_key_exists($requestedTheme, $availableThemes) ? $requestedTheme : $defaultTheme);
            $view->with('isAdminAuthenticated', $isAdminAuthenticated);
            $view->with('currentPlayer', $currentPlayer);
        });
    }
}
