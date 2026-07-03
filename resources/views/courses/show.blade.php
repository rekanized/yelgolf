<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $course->name }} | {{ config('app.name') }}</title>
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body>
        @php
            $metadataItems = array_values(array_filter([
                ['label' => 'Targets', 'value' => $course->target_type],
                ['label' => 'Tee surfaces', 'value' => $course->tee_types ? implode(', ', $course->tee_types) : null],
                ['label' => 'Land type', 'value' => $course->land_types ? implode(', ', array_map(static fn (string $value): string => \Illuminate\Support\Str::headline($value), $course->land_types)) : null],
                ['label' => 'Property', 'value' => $course->property_type ? \Illuminate\Support\Str::headline($course->property_type) : null],
                ['label' => 'Difficulty', 'value' => $course->difficulty_levels ? implode(', ', array_map(static fn (string $value): string => \Illuminate\Support\Str::headline($value), $course->difficulty_levels)) : null],
            ], static fn (array $item): bool => filled($item['value'])));

            $featureItems = array_values(array_filter([
                $course->has_bathroom ? 'Restroom available' : null,
                $course->has_drinking_water ? 'Drinking water available' : null,
                $course->is_cart_friendly ? 'Cart friendly' : null,
                $course->is_dog_friendly ? 'Dogs allowed' : null,
                $course->is_stroller_friendly ? 'Stroller friendly' : null,
            ]));

            $holeLayouts = $course->holes->groupBy('layout_order');
        @endphp

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

                    <div class="sports-utility">
                        <a class="button button-secondary" href="{{ url('/') }}">Back to courses</a>
                        <a class="button button-primary" href="{{ route('admin.login') }}">Admin login</a>
                    </div>
                </div>
            </header>

            <main class="sports-main course-show">
                <section class="sports-panel course-show__hero">
                    <div class="course-show__hero-copy">
                        <p class="eyebrow">Course profile</p>
                        <h1>{{ $course->name }}</h1>
                        <p class="hero-location">{{ $course->location_name ?? 'Imported course' }}</p>
                        @if ($course->description)
                            <p class="hero-summary">{{ $course->description }}</p>
                        @endif

                        <div class="hero-actions">
                            @if ($course->latitude && $course->longitude)
                                <a class="button button-primary" href="https://www.google.com/maps/place/{{ $course->latitude }},{{ $course->longitude }}" target="_blank" rel="noreferrer">Open map</a>
                            @endif
                        </div>
                    </div>

                    <dl class="course-show__stats">
                        <div>
                            <dt>Rating</dt>
                            <dd>{{ $course->rating ? number_format((float) $course->rating, 1).' / 5' : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>Reviews</dt>
                            <dd>{{ $course->ratings_count ? number_format($course->ratings_count) : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>Holes</dt>
                            <dd>{{ $course->holes_count ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>Established</dt>
                            <dd>{{ $course->established_year ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </section>

                @if ($metadataItems !== [] || $featureItems !== [] || $course->accessibility_description)
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">Imported facts</p>
                                <h2>Course details</h2>
                            </div>
                        </div>

                        @if ($metadataItems !== [])
                            <dl class="course-facts__grid">
                                @foreach ($metadataItems as $item)
                                    <div>
                                        <dt>{{ $item['label'] }}</dt>
                                        <dd>{{ $item['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if ($featureItems !== [])
                            <ul class="course-feature-list">
                                @foreach ($featureItems as $featureItem)
                                    <li>{{ $featureItem }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($course->accessibility_description)
                            <div class="course-accessibility">
                                <p class="eyebrow">Accessibility</p>
                                <p>
                                    @if ($course->accessibility)
                                        <strong>{{ \Illuminate\Support\Str::headline($course->accessibility) }}.</strong>
                                    @endif
                                    {{ $course->accessibility_description }}
                                </p>
                            </div>
                        @endif
                    </section>
                @endif

                @if ($holeLayouts->isNotEmpty())
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">Layout maps</p>
                                <h2>Holes by layout</h2>
                            </div>
                        </div>

                        <div class="course-layouts">
                            @foreach ($holeLayouts as $layoutHoles)
                                @php
                                    $layout = $layoutHoles->first();
                                @endphp

                                <article class="course-layout-card">
                                    <div class="course-layout-card__head">
                                        <div>
                                            <h3>{{ $layout->layout_name ?? 'Course layout' }}</h3>
                                            @if ($layout->layout_difficulty)
                                                <p class="muted">{{ \Illuminate\Support\Str::headline($layout->layout_difficulty) }}</p>
                                            @endif
                                        </div>

                                        <div class="badge">{{ $layoutHoles->count() }} holes</div>
                                    </div>

                                    <div class="course-holes-table">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th scope="col">Hole</th>
                                                    <th scope="col">Par</th>
                                                    <th scope="col">Distance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($layoutHoles as $hole)
                                                    <tr>
                                                        <td>{{ $hole->hole_label ?? $hole->number ?? 'N/A' }}</td>
                                                        <td>{{ $hole->par ?? 'N/A' }}</td>
                                                        <td>
                                                            @if ($hole->distance_meters)
                                                                {{ number_format((float) $hole->distance_meters, $hole->distance_meters == floor($hole->distance_meters) ? 0 : 1) }} m
                                                            @elseif ($hole->distance_feet)
                                                                {{ number_format((float) $hole->distance_feet, $hole->distance_feet == floor($hole->distance_feet) ? 0 : 1) }} ft
                                                            @else
                                                                N/A
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if (! empty($course->photos))
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">UDisc gallery</p>
                                <h2>Course pictures</h2>
                            </div>
                        </div>

                        <div class="course-gallery">
                            @foreach ($course->photos as $photo)
                                <a class="course-gallery__item" href="{{ $photo }}" target="_blank" rel="noreferrer">
                                    <img src="{{ $photo }}" alt="{{ $course->name }} picture from UDisc" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </main>
        </div>
    </body>
</html>