<div class="auth-wrap">
    <section class="auth-card">
        <p class="eyebrow">Admin access</p>
        <h1>Sign in</h1>
        <p class="lead">Temporary credentials are <strong>test</strong> / <strong>test</strong>. Google auth can replace this later.</p>

        <form wire:submit="authenticate">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" wire:model="username" autocomplete="username">
                @error('username') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" wire:model="password" autocomplete="current-password">
                @error('password') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            <div class="actions" style="margin-top: 22px;">
                <button class="button button-primary button-with-spinner" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                    <span wire:loading.remove wire:target="authenticate">Log in</span>
                    <span class="button-spinner-wrap" wire:loading wire:target="authenticate">
                        <span class="button-spinner" aria-hidden="true"></span>
                        Signing in...
                    </span>
                </button>
                <a class="button button-secondary" href="{{ url('/') }}">Back to courses</a>
            </div>
        </form>
    </section>
</div>