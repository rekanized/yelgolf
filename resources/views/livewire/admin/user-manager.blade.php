<div class="sports-page">
    <header class="sports-header">
        <div class="sports-topbar">
            @include('partials.brand')
        </div>

        @include('partials.sports-nav')
    </header>

    <main class="sports-main">
        <section class="sports-panel sports-panel--index">
            <div class="sports-panel__heading sports-panel__heading--stacked">
                <div>
                    <p class="eyebrow">{{ __('ui.admin.eyebrow') }}</p>
                    <h1>{{ __('ui.admin.users_title') }}</h1>
                </div>
                <p class="panel-note">{{ __('ui.admin.users_copy') }}</p>
            </div>

            @include('partials.admin-nav')
        </section>

        <section class="sports-panel">
            <div class="dashboard-header">
                <div>
                    <h2>{{ __('ui.admin.signed_in_users') }}</h2>
                    <p class="muted">{{ trans_choice('ui.admin.users_count', $users->count(), ['count' => $users->count()]) }}</p>
                </div>
            </div>

            @if ($users->isEmpty())
                <div class="empty-state">
                    <h3>{{ __('ui.admin.no_users_title') }}</h3>
                    <p class="muted">{{ __('ui.admin.no_users_copy') }}</p>
                </div>
            @else
                <div class="user-list">
                    @foreach ($users as $user)
                        <article class="user-list-item">
                            <div class="user-list-item__identity">
                                @if ($user->google_avatar)
                                    <img class="user-avatar" src="{{ $user->google_avatar }}" alt="">
                                @else
                                    <span class="user-avatar user-avatar--fallback" aria-hidden="true">{{ \Illuminate\Support\Str::of($user->name)->trim()->substr(0, 1)->upper() }}</span>
                                @endif

                                <div>
                                    <h3>{{ $user->name }}</h3>
                                    <p class="muted">{{ $user->email }}</p>
                                </div>
                            </div>

                            <div class="user-list-item__meta">
                                <span class="badge">{{ \Illuminate\Support\Str::headline($user->role) }}</span>
                                <span class="muted">{{ __('ui.admin.user_joined', ['date' => $user->created_at?->format('M j, Y') ?? __('ui.course.unknown')]) }}</span>
                                <span class="muted">{{ __('ui.admin.user_last_updated', ['time' => $user->updated_at?->diffForHumans() ?? __('ui.course.unknown')]) }}</span>
                                <span class="muted">{{ $user->google_id ? __('ui.admin.google_connected') : __('ui.admin.google_not_connected') }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</div>
