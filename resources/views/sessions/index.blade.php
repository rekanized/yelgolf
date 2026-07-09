<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $currentTheme ?? config('yelgolf.default_theme', 'dark') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ __('ui.session.index_page_title') }}</title>
        @include('partials.favicons')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @livewireStyles
    </head>
    <body>
        <div class="sports-page">
            <header class="sports-header">
                <div class="sports-topbar">
                    @include('partials.brand')

                    @livewire('player-console')
                </div>

                @include('partials.sports-nav')
            </header>

            <main class="sports-main">
                <section class="sports-panel sports-panel--index">
                    <div class="sports-panel__heading sports-panel__heading--stacked">
                        <div>
                            <p class="eyebrow">{{ __('ui.session.index_eyebrow') }}</p>
                            <h1>{{ __('ui.session.index_title') }}</h1>
                        </div>
                        <p class="panel-note">{{ __('ui.session.index_copy') }}</p>
                    </div>

                    @if ($sessions->isEmpty())
                        <div class="sports-empty">
                            <p class="eyebrow">{{ __('ui.session.index_empty_eyebrow') }}</p>
                            <h2>{{ __('ui.session.index_empty_title') }}</h2>
                            <p>{{ __('ui.session.index_empty_copy') }}</p>
                            <div class="actions">
                                <a class="button button-primary" href="{{ route('home') }}">{{ __('ui.nav.view_courses') }}</a>
                            </div>
                        </div>
                    @else
                        <div class="course-list">
                            @foreach ($sessions as $session)
                                @php
                                    $joinedCount = $session->players->filter(fn ($player) => $player->pivot->status === 'joined')->count() + ($session->hasAnonymousHostParticipant() ? 1 : 0);
                                    $invitedCount = $session->status === 'active'
                                        ? $session->players->filter(fn ($player) => $player->pivot->status === 'invited')->count()
                                        : 0;
                                    $hostName = $session->host?->name ?? $session->host_name ?? __('ui.session.host_fallback');
                                @endphp

                                <article class="course-list-item">
                                    <div class="dashboard-header">
                                        <div>
                                            <h2>{{ $session->course->name }}</h2>
                                            <p class="muted">{{ __('ui.session.invited_by', ['name' => $hostName]) }}</p>
                                        </div>
                                        <div @class([
                                            'badge',
                                            'badge--active-session' => $session->status === 'active',
                                            'badge--subtle' => $session->status !== 'active',
                                        ])>{{ __('ui.session.statuses.'.$session->status) }}</div>
                                    </div>

                                    <div class="course-admin-footer">
                                        <div class="course-admin-footer__stats">
                                            <span class="muted">{{ __('ui.session.started_at', ['time' => $session->started_at?->diffForHumans() ?? __('ui.course.na')]) }}</span>
                                            @if ($session->ended_at)
                                                <span class="muted">{{ __('ui.session.ended_at', ['time' => $session->ended_at->diffForHumans()]) }}</span>
                                            @endif
                                            <span class="muted">{{ trans_choice('ui.session.player_count', $joinedCount, ['count' => $joinedCount]) }}</span>
                                            @if ($session->status === 'active')
                                                <span class="muted">{{ trans_choice('ui.session.pending_count', $invitedCount, ['count' => $invitedCount]) }}</span>
                                            @endif
                                        </div>

                                        <div class="course-admin-footer__actions">
                                            <a class="button button-primary" href="{{ route('sessions.show', $session) }}">{{ __('ui.session.open_session') }}</a>
                                            <a class="text-link" href="{{ route('courses.show', $session->course) }}">{{ __('ui.session.back_to_course') }}</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </main>
        </div>

        @livewireScriptConfig
    </body>
</html>
