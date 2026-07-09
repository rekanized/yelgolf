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
        <section class="sports-panel auth-card">
            <div class="sports-panel__heading sports-panel__heading--stacked">
                <div>
                    <p class="eyebrow">{{ __('ui.player_auth.eyebrow') }}</p>
                    <h1>{{ __('ui.player_auth.title') }}</h1>
                </div>
                <p class="panel-note">{{ __('ui.player_auth.copy') }}</p>
            </div>

            @error('google') <p class="error-text">{{ $message }}</p> @enderror

            <div class="actions" style="margin-top: 22px;">
                <a class="button button-primary" href="{{ route('auth.google.redirect') }}">{{ __('ui.player_auth.google_submit') }}</a>
                <a class="button button-secondary" href="{{ url('/') }}">{{ __('ui.nav.back_to_courses') }}</a>
            </div>
        </section>
    </main>
</div>
