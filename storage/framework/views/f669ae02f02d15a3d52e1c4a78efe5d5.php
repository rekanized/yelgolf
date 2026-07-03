<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e(config('app.name')); ?></title>
        <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    </head>
    <body>
        <div class="sports-page">
            <header class="sports-header">
                <div class="sports-topbar">
                    <a class="sports-brand" href="<?php echo e(url('/')); ?>">
                        <span class="sports-brand__crest">YG</span>
                        <span>
                            <strong>Yelgolf</strong>
                            <span class="sports-brand__sub">Disc golf club desk</span>
                        </span>
                    </a>
                </div>

                <nav class="sports-nav" aria-label="Primary">
                    <a href="<?php echo e(url('/')); ?>">Home</a>
                    <a href="#course-list">Courses</a>
                    <a href="<?php echo e(route('admin.login')); ?>">Admin</a>
                </nav>

                <section class="sports-search" aria-label="Course search">
                    <form class="sports-search__form" method="GET" action="<?php echo e(url('/')); ?>" data-live-search-form>
                        <label class="sports-search__label" for="course-search">Find a course</label>
                        <div class="sports-search__controls">
                            <input
                                id="course-search"
                                class="sports-search__input"
                                type="search"
                                name="q"
                                value="<?php echo e($searchQuery ?? ''); ?>"
                                placeholder="Search by course, city, or description"
                                autocomplete="off"
                                data-live-search-input
                            >
                        </div>
                    </form>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($searchQuery ?? '') !== ''): ?>
                        <p class="sports-search__status">
                            Showing <?php echo e($courses->count()); ?> <?php echo e(Illuminate\Support\Str::plural('course', $courses->count())); ?> for "<?php echo e($searchQuery); ?>"
                        </p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </section>
            </header>

            <main class="sports-main">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($courses->isNotEmpty()): ?>
                    <section class="sports-panel sports-panel--index" id="course-list">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">Course list</p>
                                <h1>Courses</h1>
                            </div>
                            <p class="panel-note">Tap a course to open the full course page with more information and pictures.</p>
                        </div>

                        <div class="course-index-list">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <a class="course-index-card" href="<?php echo e(route('courses.show', $course)); ?>">
                                    <div class="course-index-card__title">
                                        <h2><?php echo e($course->name); ?></h2>
                                    </div>
                                    <dl class="course-index-card__stats">
                                        <div>
                                            <dt>Rating</dt>
                                            <dd><?php echo e($course->rating ? number_format((float) $course->rating, 1) : 'N/A'); ?></dd>
                                        </div>
                                        <div>
                                            <dt>Reviews</dt>
                                            <dd><?php echo e($course->ratings_count ? number_format($course->ratings_count) : 'N/A'); ?></dd>
                                        </div>
                                        <div>
                                            <dt>Holes</dt>
                                            <dd><?php echo e($course->holes_count ?? 'N/A'); ?></dd>
                                        </div>
                                    </dl>
                                </a>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="sports-empty">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($searchQuery ?? '') !== ''): ?>
                            <p class="eyebrow eyebrow-light">Search results</p>
                            <h1>No matching courses</h1>
                            <p>Try a different search term or remove the current search text to browse all courses again.</p>
                        <?php else: ?>
                            <p class="eyebrow eyebrow-light">Matchday pending</p>
                            <h1>Build the first card</h1>
                            <p>No courses are available yet. Use the admin desk to add the first course and the front page will switch into matchday mode.</p>
                            <a class="button button-primary" href="<?php echo e(route('admin.login')); ?>">Open admin desk</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </section>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
</html><?php /**PATH /var/www/yelgolf/resources/views/welcome.blade.php ENDPATH**/ ?>