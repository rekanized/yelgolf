<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $currentTheme ?? config('yelgolf.default_theme', 'dark') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('ui.settings.page_title') }} | {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body>
        <div class="sports-page">
            <header class="sports-header">
                <div class="sports-topbar">
                    <a class="sports-brand" href="{{ url('/') }}">
                        <span class="sports-brand__crest">YG</span>
                        <span>
                            <strong>Yelgolf</strong>
                            <span class="sports-brand__sub">{{ __('ui.brand.subtitle') }}</span>
                        </span>
                    </a>
                </div>

                @include('partials.sports-nav')
            </header>

            <main class="sports-main">
                <section class="sports-panel settings-page">
                    <div class="sports-panel__heading sports-panel__heading--stacked">
                        <div>
                            <p class="eyebrow">{{ __('ui.settings.eyebrow') }}</p>
                            <h1>{{ __('ui.settings.title') }}</h1>
                        </div>
                        <p class="panel-note">{{ __('ui.settings.copy') }}</p>
                    </div>

                    <form class="settings-form settings-form--page" method="POST" action="{{ route('preferences.update') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">

                        <fieldset class="settings-group">
                            <legend>{{ __('ui.settings.language') }}</legend>

                            <div class="settings-choice-grid">
                                @foreach (array_keys($availableLocales ?? []) as $locale)
                                    <label class="settings-choice">
                                        <input type="radio" name="locale" value="{{ $locale }}" @checked(app()->getLocale() === $locale)>
                                        <span>{{ __('ui.settings.locales.'.$locale) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="settings-group">
                            <legend>{{ __('ui.settings.theme') }}</legend>

                            <div class="settings-choice-grid">
                                @foreach (array_keys($availableThemes ?? []) as $theme)
                                    <label class="settings-choice">
                                        <input type="radio" name="theme" value="{{ $theme }}" @checked(($currentTheme ?? config('yelgolf.default_theme', 'dark')) === $theme)>
                                        <span>{{ __('ui.settings.themes.'.$theme) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <button class="button button-primary settings-form__submit" type="submit">{{ __('ui.settings.apply') }}</button>
                    </form>
                </section>
            </main>
        </div>
    </body>
</html>
