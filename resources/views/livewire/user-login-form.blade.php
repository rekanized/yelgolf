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
                <p class="panel-note">{!! __('ui.player_auth.copy', ['login' => '<strong>test</strong>', 'password' => '<strong>test</strong>']) !!}</p>
            </div>

            <form wire:submit="authenticate">
                <div class="field">
                    <label for="player-login">{{ __('ui.player_auth.login') }}</label>
                    <input id="player-login" type="text" wire:model="login" autocomplete="username">
                    @error('login') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="player-password">{{ __('ui.player_auth.password') }}</label>
                    <input id="player-password" type="password" wire:model="password" autocomplete="current-password">
                    @error('password') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="actions" style="margin-top: 22px;">
                    <button class="button button-primary button-with-spinner" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                        <span wire:loading.remove wire:target="authenticate">{{ __('ui.player_auth.submit') }}</span>
                        <span class="button-spinner-wrap" wire:loading wire:target="authenticate">
                            <span class="button-spinner" aria-hidden="true"></span>
                            {{ __('ui.player_auth.signing_in') }}
                        </span>
                    </button>
                    <a class="button button-secondary" href="{{ route('admin.login') }}">{{ __('ui.player_auth.admin_login') }}</a>
                    <a class="button button-secondary" href="{{ url('/') }}">{{ __('ui.nav.back_to_courses') }}</a>
                </div>
            </form>
        </section>
    </main>
</div>