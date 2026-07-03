<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
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
                            <span class="sports-brand__sub">Disc golf club desk</span>
                        </span>
                    </a>
                </div>

                <nav class="sports-nav" aria-label="Primary">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="#course-list">Courses</a>
                    <a href="{{ route('admin.login') }}">Admin</a>
                </nav>

                <section class="sports-search" aria-label="Course search">
                    <form class="sports-search__form" method="GET" action="{{ url('/') }}" data-live-search-form>
                        <label class="sports-search__label" for="course-search">Find a course</label>
                        <div class="sports-search__controls">
                            <input
                                id="course-search"
                                class="sports-search__input"
                                type="search"
                                name="q"
                                value="{{ $searchQuery ?? '' }}"
                                placeholder="Search by course, city, or description"
                                autocomplete="off"
                                data-live-search-input
                            >
                        </div>
                    </form>

                    @if (($searchQuery ?? '') !== '')
                        <p class="sports-search__status">
                            Showing {{ $courses->count() }} {{ Illuminate\Support\Str::plural('course', $courses->count()) }} for "{{ $searchQuery }}"
                        </p>
                    @endif
                </section>
            </header>

            <main class="sports-main">
                @if ($courses->isNotEmpty())
                    <section class="sports-panel sports-panel--index" id="course-list">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">Course list</p>
                                <h1>Courses</h1>
                            </div>
                            <p class="panel-note">Tap a course to open the full course page with more information and pictures.</p>
                        </div>

                        <div class="course-index-list">
                            @foreach ($courses as $course)
                                <a class="course-index-card" href="{{ route('courses.show', $course) }}">
                                    <div class="course-index-card__title">
                                        <h2>{{ $course->name }}</h2>
                                    </div>
                                    <dl class="course-index-card__stats">
                                        <div>
                                            <dt>Rating</dt>
                                            <dd>{{ $course->rating ? number_format((float) $course->rating, 1) : 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt>Reviews</dt>
                                            <dd>{{ $course->ratings_count ? number_format($course->ratings_count) : 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt>Holes</dt>
                                            <dd>{{ $course->holes_count ?? 'N/A' }}</dd>
                                        </div>
                                    </dl>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @else
                    <section class="sports-empty">
                        @if (($searchQuery ?? '') !== '')
                            <p class="eyebrow eyebrow-light">Search results</p>
                            <h1>No matching courses</h1>
                            <p>Try a different search term or remove the current search text to browse all courses again.</p>
                        @else
                            <p class="eyebrow eyebrow-light">Matchday pending</p>
                            <h1>Build the first card</h1>
                            <p>No courses are available yet. Use the admin desk to add the first course and the front page will switch into matchday mode.</p>
                            <a class="button button-primary" href="{{ route('admin.login') }}">Open admin desk</a>
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
    </body>
</html>