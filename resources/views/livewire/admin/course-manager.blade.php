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
                    <h1>{{ __('ui.admin.importer_title') }}</h1>
                </div>
                <p class="panel-note">{{ __('ui.admin.same_data_copy') }}</p>
            </div>

            @include('partials.admin-nav')

            <div class="panel-grid">
                <article class="panel">
                    <div class="dashboard-header">
                        <div>
                            <h2>{{ __('ui.admin.add_course_title') }}</h2>
                            <p class="lead">{{ __('ui.admin.add_course_copy', ['url' => 'https://udisc.com/courses/haesthagen-M8Wu']) }}</p>
                        </div>
                        <div class="badge">{{ __('ui.admin.imported_count', ['count' => $courses->count()]) }}</div>
                    </div>

                    <form class="import-form" wire:submit="importCourse">
                        <div class="field">
                            <label for="udisc-url">{{ __('ui.admin.udisc_url') }}</label>
                            <input id="udisc-url" type="url" wire:model="udiscUrl" placeholder="https://udisc.com/courses/haesthagen-M8Wu">
                            <p class="field-help">{{ __('ui.admin.udisc_help') }}</p>
                            @error('udiscUrl') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div class="actions">
                            <button class="button button-primary button-with-spinner" type="submit" wire:loading.attr="disabled" wire:target="importCourse">
                                <span wire:loading.remove wire:target="importCourse">{{ __('ui.admin.import_course') }}</span>
                                <span class="button-spinner-wrap" wire:loading wire:target="importCourse">
                                    <span class="button-spinner" aria-hidden="true"></span>
                                    {{ __('ui.admin.importing') }}
                                </span>
                            </button>
                        </div>
                    </form>
                </article>

                <article class="panel">
                    <h2>{{ __('ui.admin.what_gets_stored') }}</h2>
                    <div class="course-list">
                        <div class="stat">
                            <div class="muted-label">{{ __('ui.admin.source') }}</div>
                            <strong>{{ __('ui.admin.source_copy') }}</strong>
                        </div>
                        <div class="stat">
                            <div class="muted-label">{{ __('ui.admin.summary') }}</div>
                            <strong>{{ __('ui.admin.summary_copy') }}</strong>
                        </div>
                        <div class="stat">
                            <div class="muted-label">{{ __('ui.admin.map') }}</div>
                            <strong>{{ __('ui.admin.map_copy') }}</strong>
                        </div>
                        <div class="stat">
                            <div class="muted-label">{{ __('ui.admin.details') }}</div>
                            <strong>{{ __('ui.admin.details_copy') }}</strong>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="sports-panel">
            <div class="dashboard-header">
                <div>
                    <h2>{{ __('ui.admin.imported_courses') }}</h2>
                    <p class="muted">{{ __('ui.admin.same_data_copy') }}</p>
                </div>
            </div>

            @if ($courses->isEmpty())
                <div class="empty-state">
                    <h3>{{ __('ui.admin.no_courses_title') }}</h3>
                    <p class="muted">{{ __('ui.admin.no_courses_copy') }}</p>
                </div>
            @else
                <div class="course-list">
                    @foreach ($courses as $course)
                        <article class="course-list-item">
                            <div class="dashboard-header">
                                <div>
                                    <h3>
                                        <a class="text-link" href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
                                    </h3>
                                    <p class="muted">{{ $course->location_name ?? __('ui.admin.location_unavailable') }}</p>
                                </div>
                                @if ($course->rating)
                                    <div class="badge">{{ number_format((float) $course->rating, 1) }} / 5</div>
                                @endif
                            </div>

                            <p class="muted">{{ $course->description ?: __('ui.admin.no_description') }}</p>

                            <div class="course-admin-footer">
                                <div class="course-admin-footer__stats">
                                    <span class="muted">{{ __('ui.admin.stat_holes', ['value' => $course->holes_count ?? __('ui.course.unknown')]) }}</span>
                                    <span class="muted">{{ __('ui.admin.stat_established', ['value' => $course->established_year ?? __('ui.course.unknown')]) }}</span>
                                    <span class="muted">{{ __('ui.admin.stat_difficulty', ['value' => $course->difficulty_levels ? implode(', ', array_map(static fn (string $value): string => \Illuminate\Support\Facades\Lang::has('ui.course.values.difficulty.'.$value) ? __('ui.course.values.difficulty.'.$value) : \Illuminate\Support\Str::headline($value), $course->difficulty_levels)) : __('ui.course.unknown')]) }}</span>
                                    <span class="muted">{{ __('ui.admin.stat_layouts', ['value' => $course->holes->groupBy('layout_order')->count()]) }}</span>
                                </div>
                                <div class="course-admin-footer__actions">
                                    <button class="button button-secondary button-with-spinner" type="button" wire:click="updateCourse({{ $course->id }})" wire:loading.attr="disabled" wire:target="updateCourse({{ $course->id }})">
                                        <span wire:loading.remove wire:target="updateCourse({{ $course->id }})">{{ __('ui.admin.update') }}</span>
                                        <span class="button-spinner-wrap" wire:loading wire:target="updateCourse({{ $course->id }})">
                                            <span class="button-spinner" aria-hidden="true"></span>
                                            {{ __('ui.admin.updating') }}
                                        </span>
                                    </button>
                                    <a class="text-link" href="{{ $course->udisc_url }}" target="_blank" rel="noopener noreferrer">{{ __('ui.admin.open_source') }}</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</div>
