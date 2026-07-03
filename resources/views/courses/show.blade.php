<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $currentTheme ?? config('yelgolf.default_theme', 'dark') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $course->name }} | {{ config('app.name') }}</title>
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

            $translateCourseValueList = static function (string $group, ?array $values) use ($translateCourseValue): ?string {
                if (! is_array($values) || $values === []) {
                    return null;
                }

                return collect($values)
                    ->map(fn (string $value): string => $translateCourseValue($group, $value) ?? $value)
                    ->implode(', ');
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

            $difficultyItems = $mapDifficultyItems($course->difficulty_levels);

            $metadataItems = array_values(array_filter([
                ['label' => __('ui.course.targets'), 'value' => $course->target_type, 'icon' => 'adjust'],
                ['label' => __('ui.course.tee_surfaces'), 'value' => $course->tee_types ? implode(', ', $course->tee_types) : null, 'icon' => 'texture'],
                ['label' => __('ui.course.land_type'), 'value' => $translateCourseValueList('land_type', $course->land_types), 'icon' => 'terrain'],
                ['label' => __('ui.course.property'), 'value' => $course->property_type ? $translateCourseValue('property', $course->property_type) : null, 'icon' => 'domain'],
                ['label' => __('ui.course.difficulty'), 'value' => null, 'icon' => 'signal_cellular_alt', 'difficulty_items' => $difficultyItems],
            ], static fn (array $item): bool => filled($item['value'] ?? null) || (($item['difficulty_items'] ?? []) !== [])));

            $featureItems = array_values(array_filter([
                $course->has_bathroom ? ['label' => __('ui.course.features.restroom'), 'icon' => 'wc'] : null,
                $course->has_drinking_water ? ['label' => __('ui.course.features.water'), 'icon' => 'water_drop'] : null,
                $course->is_cart_friendly ? ['label' => __('ui.course.features.cart'), 'icon' => 'luggage'] : null,
                $course->is_dog_friendly ? ['label' => __('ui.course.features.dogs'), 'icon' => 'pets'] : null,
                $course->is_stroller_friendly ? ['label' => __('ui.course.features.stroller'), 'icon' => 'stroller'] : null,
            ]));

            $statItems = [
                ['label' => __('ui.course.rating'), 'value' => $course->rating ? number_format((float) $course->rating, 1).' / 5' : __('ui.course.na'), 'icon' => 'star'],
                ['label' => __('ui.course.reviews'), 'value' => $course->ratings_count ? number_format($course->ratings_count) : __('ui.course.na'), 'icon' => 'forum'],
                ['label' => __('ui.course.holes'), 'value' => $course->holes_count ?? __('ui.course.na'), 'icon' => 'flag'],
                ['label' => __('ui.course.established'), 'value' => $course->established_year ?? __('ui.course.na'), 'icon' => 'event'],
            ];

            $holeLayouts = $course->holes->groupBy('layout_order');
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
            </header>

            <main class="sports-main course-show">
                <section class="sports-panel course-show__hero">
                    <div class="course-show__hero-copy">
                        <p class="eyebrow">{{ __('ui.course.profile_eyebrow') }}</p>
                        <h1>{{ $course->name }}</h1>
                        <p class="hero-location">{{ $course->location_name ?? __('ui.course.imported_course') }}</p>
                        @if ($course->description)
                            <p class="hero-summary">{{ $course->description }}</p>
                        @endif

                        <div class="hero-actions">
                            @if ($course->latitude && $course->longitude)
                                <a class="button button-primary" href="https://www.google.com/maps/place/{{ $course->latitude }},{{ $course->longitude }}" target="_blank" rel="noreferrer">{{ __('ui.course.open_map') }}</a>
                            @endif

                            <form class="button-form" method="POST" action="{{ route('sessions.store', $course) }}">
                                @csrf
                                <button class="button button-secondary" type="submit">{{ __('ui.session.start') }}</button>
                            </form>
                        </div>
                    </div>

                    <dl class="course-show__stats">
                        @foreach ($statItems as $item)
                            <div class="course-stat-card">
                                <dt>
                                    <span class="course-stat-card__icon material-symbols-outlined" aria-hidden="true">{{ $item['icon'] }}</span>
                                    <span>{{ $item['label'] }}</span>
                                </dt>
                                <dd>{{ $item['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                @if ($metadataItems !== [] || $featureItems !== [] || $course->accessibility_description)
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">{{ __('ui.course.imported_facts_eyebrow') }}</p>
                                <h2>{{ __('ui.course.details_title') }}</h2>
                            </div>
                        </div>

                        @if ($metadataItems !== [])
                            <dl class="course-facts__grid">
                                @foreach ($metadataItems as $item)
                                    <div class="course-fact-card">
                                        <dt>
                                            <span class="course-fact-card__icon material-symbols-outlined" aria-hidden="true">{{ $item['icon'] }}</span>
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
                        @endif

                        @if ($featureItems !== [])
                            <ul class="course-feature-list">
                                @foreach ($featureItems as $featureItem)
                                    <li>
                                        <span class="material-symbols-outlined" aria-hidden="true">{{ $featureItem['icon'] }}</span>
                                        <span>{{ $featureItem['label'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($course->accessibility_description)
                            <div class="course-accessibility">
                                <p class="eyebrow course-accessibility__eyebrow">
                                    <span class="material-symbols-outlined" aria-hidden="true">accessible</span>
                                    <span>{{ __('ui.course.accessibility') }}</span>
                                </p>
                                <p>
                                    @if ($course->accessibility)
                                        <strong>{{ $translateCourseValue('accessibility', $course->accessibility) }}.</strong>
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
                                <p class="eyebrow">{{ __('ui.course.layout_maps_eyebrow') }}</p>
                                <h2>{{ __('ui.course.holes_by_layout') }}</h2>
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
                                            <h3>{{ $layout->layout_name ?? __('ui.course.layout_fallback') }}</h3>
                                            @if ($layout->layout_difficulty)
                                                <p class="course-layout-card__difficulty">
                                                    <span class="course-difficulty-token course-difficulty-token--{{ $layout->layout_difficulty }}">{{ $translateCourseValue('difficulty', $layout->layout_difficulty) }}</span>
                                                </p>
                                            @endif

                                            @if ($layout->layout_caddie_book_url)
                                                <div class="course-layout-card__actions">
                                                    <a class="button button-secondary" href="{{ $layout->layout_caddie_book_url }}" target="_blank" rel="noreferrer">{{ __('ui.course.layout_map') }}</a>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="badge">{{ trans_choice('ui.course.hole_count_label', $layoutHoles->count(), ['count' => $layoutHoles->count()]) }}</div>
                                    </div>

                                    <div class="course-holes-table">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th scope="col">{{ __('ui.course.hole') }}</th>
                                                    <th scope="col">{{ __('ui.course.par') }}</th>
                                                    <th scope="col">{{ __('ui.course.distance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($layoutHoles as $hole)
                                                    <tr>
                                                        <td>{{ $hole->hole_label ?? $hole->number ?? __('ui.course.na') }}</td>
                                                        <td>{{ $hole->par ?? __('ui.course.na') }}</td>
                                                        <td>
                                                            @if ($hole->distance_meters)
                                                                {{ number_format((float) $hole->distance_meters, $hole->distance_meters == floor($hole->distance_meters) ? 0 : 1) }} m
                                                            @elseif ($hole->distance_feet)
                                                                {{ number_format((float) $hole->distance_feet, $hole->distance_feet == floor($hole->distance_feet) ? 0 : 1) }} ft
                                                            @else
                                                                {{ __('ui.course.na') }}
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
                                <p class="eyebrow">{{ __('ui.course.gallery_eyebrow') }}</p>
                                <h2>{{ __('ui.course.gallery_title') }}</h2>
                            </div>
                        </div>

                        <div class="course-gallery">
                            @foreach ($course->photos as $photo)
                                <a class="course-gallery__item" href="{{ $photo }}" target="_blank" rel="noreferrer">
                                    <img src="{{ $photo }}" alt="{{ __('ui.course.photo_alt', ['name' => $course->name]) }}" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </main>
        </div>
        @livewireScripts
    </body>
</html>