<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $currentTheme ?? config('yelgolf.default_theme', 'dark') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @livewireStyles
    </head>
    <body>
        @php
            $translateCourseValue = static function (string $group, ?string $value): ?string {
                if (! filled($value)) {
                    return null;
                }

                $translationKey = 'ui.course.values.'.$group.'.'.$value;

                return \Illuminate\Support\Facades\Lang::has($translationKey)
                    ? __($translationKey)
                    : \Illuminate\Support\Str::headline($value);
            };

            $difficultyOrder = [
                'beginner' => 1,
                'intermediate' => 2,
                'challenging' => 3,
                'advanced' => 4,
            ];

            $mapDifficultyItems = static function (?array $values) use ($difficultyOrder, $translateCourseValue): array {
                return collect($values ?? [])
                    ->filter(static fn (?string $value): bool => filled($value))
                    ->sortBy(static fn (string $value): int => $difficultyOrder[$value] ?? PHP_INT_MAX)
                    ->map(static fn (string $value): array => [
                        'level' => $value,
                        'label' => $translateCourseValue('difficulty', $value) ?? $value,
                    ])
                    ->values()
                    ->all();
            };
        @endphp

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

                    @livewire('player-console')
                </div>

                @include('partials.sports-nav')

                <section class="sports-search" aria-label="Course search">
                    <form class="sports-search__form" method="GET" action="{{ url('/') }}" data-live-search-form>
                        <label class="sports-search__label" for="course-search">{{ __('ui.search.label') }}</label>
                        <div class="sports-search__controls">
                            <input
                                id="course-search"
                                class="sports-search__input"
                                type="search"
                                name="q"
                                value="{{ $searchQuery ?? '' }}"
                                placeholder="{{ __('ui.search.placeholder') }}"
                                autocomplete="off"
                                data-live-search-input
                            >
                        </div>
                    </form>

                    @if (($searchQuery ?? '') !== '')
                        <p class="sports-search__status">
                            {{ trans_choice('ui.search.showing', $courses->count(), ['count' => $courses->count(), 'query' => $searchQuery]) }}
                        </p>
                    @endif
                </section>
            </header>

            <main class="sports-main">
                @if (($activeSessions ?? collect())->isNotEmpty())
                    <section class="sports-panel sports-panel--index" id="active-sessions">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">{{ __('ui.home.active_sessions_eyebrow') }}</p>
                                <h2>{{ __('ui.home.active_sessions_title') }}</h2>
                            </div>
                            <p class="panel-note">{{ __('ui.home.active_sessions_note') }}</p>
                        </div>

                        <div class="course-list">
                            @foreach ($activeSessions as $activeSession)
                                @php
                                    $joinedCount = $activeSession->players->filter(fn ($player) => $player->pivot->status === 'joined')->count() + ($activeSession->hasAnonymousHostParticipant() ? 1 : 0);
                                    $invitedCount = $activeSession->players->filter(fn ($player) => $player->pivot->status === 'invited')->count();
                                    $hostName = $activeSession->host?->name ?? $activeSession->host_name ?? __('ui.session.host_fallback');
                                @endphp

                                <article class="course-list-item">
                                    <div class="dashboard-header">
                                        <div>
                                            <h3>{{ $activeSession->course->name }}</h3>
                                            <p class="muted">{{ __('ui.session.invited_by', ['name' => $hostName]) }}</p>
                                        </div>
                                        <div class="badge">{{ $activeSession->started_at?->diffForHumans() ?? __('ui.course.na') }}</div>
                                    </div>

                                    <div class="course-admin-footer">
                                        <div class="course-admin-footer__stats">
                                            <span class="muted">{{ trans_choice('ui.session.player_count', $joinedCount, ['count' => $joinedCount]) }}</span>
                                            <span class="muted">{{ trans_choice('ui.session.pending_count', $invitedCount, ['count' => $invitedCount]) }}</span>
                                        </div>

                                        <div class="course-admin-footer__actions">
                                            <a class="button button-primary" href="{{ route('sessions.show', $activeSession) }}">{{ __('ui.session.open_session') }}</a>
                                            <a class="text-link" href="{{ route('courses.show', $activeSession->course) }}">{{ __('ui.session.back_to_course') }}</a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($courses->isNotEmpty())
                    <section class="sports-panel sports-panel--index" id="course-list">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">{{ __('ui.home.list_eyebrow') }}</p>
                                <h1>{{ __('ui.home.title') }}</h1>
                            </div>
                            <p class="panel-note">{{ __('ui.home.panel_note') }}</p>
                        </div>

                        <div class="course-index-list">
                            @foreach ($courses as $course)
                                @php
                                    $difficultyItems = $mapDifficultyItems($course->difficulty_levels);

                                    $courseStatItems = [
                                        ['label' => __('ui.course.rating'), 'value' => $course->rating ? number_format((float) $course->rating, 1) : __('ui.course.na'), 'icon' => 'star'],
                                        ['label' => __('ui.course.reviews'), 'value' => $course->ratings_count ? number_format($course->ratings_count) : __('ui.course.na'), 'icon' => 'forum'],
                                        ['label' => __('ui.course.holes'), 'value' => $course->holes_count ?? __('ui.course.na'), 'icon' => 'flag'],
                                        ['label' => __('ui.course.difficulty'), 'value' => $difficultyItems === [] ? __('ui.course.unknown') : null, 'icon' => 'signal_cellular_alt', 'difficulty_items' => $difficultyItems],
                                    ];
                                @endphp

                                <a class="course-index-card" href="{{ route('courses.show', $course) }}">
                                    <div class="course-index-card__title">
                                        <h2>{{ $course->name }}</h2>
                                    </div>
                                    <dl class="course-index-card__stats">
                                        @foreach ($courseStatItems as $item)
                                            <div class="course-stat-card">
                                                <dt>
                                                    <span class="course-stat-card__icon material-symbols-outlined" aria-hidden="true">{{ $item['icon'] }}</span>
                                                    <span>{{ $item['label'] }}</span>
                                                </dt>
                                                <dd>
                                                    @if (($item['difficulty_items'] ?? []) !== [])
                                                        <span class="course-difficulty-list">
                                                            @foreach ($item['difficulty_items'] as $difficultyItem)
                                                                <span class="course-difficulty-token course-difficulty-token--{{ $difficultyItem['level'] }}">{{ $difficultyItem['label'] }}</span>
                                                            @endforeach
                                                        </span>
                                                    @else
                                                        {{ $item['value'] }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @else
                    <section class="sports-empty">
                        @if (($searchQuery ?? '') !== '')
                            <p class="eyebrow eyebrow-light">{{ __('ui.home.search_results_eyebrow') }}</p>
                            <h1>{{ __('ui.home.no_matches_title') }}</h1>
                            <p>{{ __('ui.home.no_matches_copy') }}</p>
                        @else
                            <p class="eyebrow eyebrow-light">{{ __('ui.home.empty_eyebrow') }}</p>
                            <h1>{{ __('ui.home.empty_title') }}</h1>
                            <p>{{ __('ui.home.empty_copy') }}</p>
                            <a class="button button-primary" href="{{ route('admin.login') }}">{{ __('ui.home.open_login') }}</a>
                        @endif
                    </section>
                @endif
            </main>
        </div>

        <script>
            (() => {
                const form = document.querySelector('[data-live-search-form]');
                const input = document.querySelector('[data-live-search-input]');

                if (!form || !input) {
                    return;
                }

                let debounceTimer = null;
                const initialValue = input.value;

                const submitSearch = () => {
                    const url = new URL(form.action, window.location.origin);
                    const value = input.value.trim();

                    if (value !== '') {
                        url.searchParams.set('q', value);
                    }

                    if (value === '') {
                        url.searchParams.delete('q');
                    }

                    if (url.toString() !== window.location.href) {
                        window.location.assign(url.toString());
                    }
                };

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    window.clearTimeout(debounceTimer);
                    submitSearch();
                });

                input.addEventListener('input', () => {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = window.setTimeout(submitSearch, 250);
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        window.clearTimeout(debounceTimer);
                        submitSearch();
                    }

                    if (event.key === 'Escape' && input.value !== '') {
                        input.value = '';
                        window.clearTimeout(debounceTimer);
                        debounceTimer = window.setTimeout(submitSearch, 100);
                    }
                });

                if (initialValue !== '' && document.activeElement !== input) {
                    input.setSelectionRange(initialValue.length, initialValue.length);
                }
            })();
        </script>
        @livewireScripts
    </body>
</html>