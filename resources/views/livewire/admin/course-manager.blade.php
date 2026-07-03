<div class="admin-shell">
    <header class="topbar">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Course importer</h1>
        </div>

        <div class="actions">
            <a class="button button-secondary" href="{{ url('/') }}">View courses</a>
            <form class="logout-form" method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="button button-primary" type="submit">Log out</button>
            </form>
        </div>
    </header>

    <section class="panel-grid">
        <article class="panel">
            <div class="dashboard-header">
                <div>
                    <h2>Add a UDisc course</h2>
                    <p class="lead">Paste a URL like https://udisc.com/courses/haesthagen-M8Wu. The importer fetches the course page, extracts structured course facts, amenities, and hole layouts, and stores them in SQLite.</p>
                </div>
                <div class="badge">{{ $courses->count() }} imported</div>
            </div>

            <form class="import-form" wire:submit="importCourse">
                <div class="field">
                    <label for="udisc-url">UDisc URL</label>
                    <input id="udisc-url" type="url" wire:model="udiscUrl" placeholder="https://udisc.com/courses/haesthagen-M8Wu">
                    <p class="field-help">Only public UDisc course pages are supported.</p>
                    @error('udiscUrl') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="actions">
                    <button class="button button-primary button-with-spinner" type="submit" wire:loading.attr="disabled" wire:target="importCourse">
                        <span wire:loading.remove wire:target="importCourse">Import course</span>
                        <span class="button-spinner-wrap" wire:loading wire:target="importCourse">
                            <span class="button-spinner" aria-hidden="true"></span>
                            Importing...
                        </span>
                    </button>
                </div>
            </form>
        </article>

        <article class="panel">
            <h2>What gets stored</h2>
            <div class="course-list">
                <div class="stat">
                    <div class="muted-label">Source</div>
                    <strong>Normalized UDisc URL</strong>
                </div>
                <div class="stat">
                    <div class="muted-label">Summary</div>
                    <strong>Name, location, holes, rating, difficulty</strong>
                </div>
                <div class="stat">
                    <div class="muted-label">Map</div>
                    <strong>Latitude and longitude</strong>
                </div>
                <div class="stat">
                    <div class="muted-label">Details</div>
                    <strong>Amenities, tee/target type, layout holes</strong>
                </div>
            </div>
        </article>
    </section>

    <section class="panel" style="margin-top: 24px;">
        <div class="dashboard-header">
            <div>
                <h2>Imported courses</h2>
                <p class="muted">This is the same data that appears on the public welcome page.</p>
            </div>
        </div>

        @if ($courses->isEmpty())
            <div class="empty-state">
                <h3>No courses yet</h3>
                <p class="muted">Import your first UDisc course to start building the directory.</p>
            </div>
        @else
            <div class="course-list">
                @foreach ($courses as $course)
                    <article class="course-list-item">
                        <div class="dashboard-header">
                            <div>
                                <h3>{{ $course->name }}</h3>
                                <p class="muted">{{ $course->location_name ?? 'Location unavailable' }}</p>
                            </div>
                            @if ($course->rating)
                                <div class="badge">{{ number_format((float) $course->rating, 1) }} / 5</div>
                            @endif
                        </div>

                        <p class="muted">{{ $course->description ?: 'No course description was found during import.' }}</p>

                        <div class="course-admin-footer">
                            <div class="course-admin-footer__stats">
                                <span class="muted">Holes: {{ $course->holes_count ?? 'Unknown' }}</span>
                                <span class="muted">Established: {{ $course->established_year ?? 'Unknown' }}</span>
                                <span class="muted">Difficulty: {{ $course->difficulty_levels ? implode(', ', array_map(static fn (string $value): string => \Illuminate\Support\Str::headline($value), $course->difficulty_levels)) : 'Unknown' }}</span>
                                <span class="muted">Layouts: {{ $course->holes->groupBy('layout_order')->count() }}</span>
                            </div>
                            <div class="course-admin-footer__actions">
                                <button class="button button-secondary button-with-spinner" type="button" wire:click="updateCourse({{ $course->id }})" wire:loading.attr="disabled" wire:target="updateCourse({{ $course->id }})">
                                    <span wire:loading.remove wire:target="updateCourse({{ $course->id }})">Update</span>
                                    <span class="button-spinner-wrap" wire:loading wire:target="updateCourse({{ $course->id }})">
                                        <span class="button-spinner" aria-hidden="true"></span>
                                        Updating...
                                    </span>
                                </button>
                                <a class="text-link" href="{{ $course->udisc_url }}" target="_blank" rel="noreferrer">Open source</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>