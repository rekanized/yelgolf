<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e($course->name); ?> | <?php echo e(config('app.name')); ?></title>
        <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    </head>
    <body>
        <?php
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
        ?>

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

                    <div class="sports-utility">
                        <a class="button button-secondary" href="<?php echo e(url('/')); ?>">Back to courses</a>
                        <a class="button button-primary" href="<?php echo e(route('admin.login')); ?>">Admin login</a>
                    </div>
                </div>
            </header>

            <main class="sports-main course-show">
                <section class="sports-panel course-show__hero">
                    <div class="course-show__hero-copy">
                        <p class="eyebrow">Course profile</p>
                        <h1><?php echo e($course->name); ?></h1>
                        <p class="hero-location"><?php echo e($course->location_name ?? 'Imported course'); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($course->description): ?>
                            <p class="hero-summary"><?php echo e($course->description); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="hero-actions">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($course->latitude && $course->longitude): ?>
                                <a class="button button-primary" href="https://www.google.com/maps/place/<?php echo e($course->latitude); ?>,<?php echo e($course->longitude); ?>" target="_blank" rel="noreferrer">Open map</a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <dl class="course-show__stats">
                        <div>
                            <dt>Rating</dt>
                            <dd><?php echo e($course->rating ? number_format((float) $course->rating, 1).' / 5' : 'N/A'); ?></dd>
                        </div>
                        <div>
                            <dt>Reviews</dt>
                            <dd><?php echo e($course->ratings_count ? number_format($course->ratings_count) : 'N/A'); ?></dd>
                        </div>
                        <div>
                            <dt>Holes</dt>
                            <dd><?php echo e($course->holes_count ?? 'N/A'); ?></dd>
                        </div>
                        <div>
                            <dt>Established</dt>
                            <dd><?php echo e($course->established_year ?? 'N/A'); ?></dd>
                        </div>
                    </dl>
                </section>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($metadataItems !== [] || $featureItems !== [] || $course->accessibility_description): ?>
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">Imported facts</p>
                                <h2>Course details</h2>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($metadataItems !== []): ?>
                            <dl class="course-facts__grid">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $metadataItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div>
                                        <dt><?php echo e($item['label']); ?></dt>
                                        <dd><?php echo e($item['value']); ?></dd>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </dl>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($featureItems !== []): ?>
                            <ul class="course-feature-list">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $featureItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $featureItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <li><?php echo e($featureItem); ?></li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </ul>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($course->accessibility_description): ?>
                            <div class="course-accessibility">
                                <p class="eyebrow">Accessibility</p>
                                <p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($course->accessibility): ?>
                                        <strong><?php echo e(\Illuminate\Support\Str::headline($course->accessibility)); ?>.</strong>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php echo e($course->accessibility_description); ?>

                                </p>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </section>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($holeLayouts->isNotEmpty()): ?>
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">Layout maps</p>
                                <h2>Holes by layout</h2>
                            </div>
                        </div>

                        <div class="course-layouts">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $holeLayouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $layoutHoles): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <?php
                                    $layout = $layoutHoles->first();
                                ?>

                                <article class="course-layout-card">
                                    <div class="course-layout-card__head">
                                        <div>
                                            <h3><?php echo e($layout->layout_name ?? 'Course layout'); ?></h3>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($layout->layout_difficulty): ?>
                                                <p class="muted"><?php echo e(\Illuminate\Support\Str::headline($layout->layout_difficulty)); ?></p>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div class="badge"><?php echo e($layoutHoles->count()); ?> holes</div>
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
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $layoutHoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hole): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                    <tr>
                                                        <td><?php echo e($hole->hole_label ?? $hole->number ?? 'N/A'); ?></td>
                                                        <td><?php echo e($hole->par ?? 'N/A'); ?></td>
                                                        <td>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hole->distance_meters): ?>
                                                                <?php echo e(number_format((float) $hole->distance_meters, $hole->distance_meters == floor($hole->distance_meters) ? 0 : 1)); ?> m
                                                            <?php elseif($hole->distance_feet): ?>
                                                                <?php echo e(number_format((float) $hole->distance_feet, $hole->distance_feet == floor($hole->distance_feet) ? 0 : 1)); ?> ft
                                                            <?php else: ?>
                                                                N/A
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($course->photos)): ?>
                    <section class="sports-panel">
                        <div class="sports-panel__heading sports-panel__heading--stacked">
                            <div>
                                <p class="eyebrow">UDisc gallery</p>
                                <h2>Course pictures</h2>
                            </div>
                        </div>

                        <div class="course-gallery">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $course->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <a class="course-gallery__item" href="<?php echo e($photo); ?>" target="_blank" rel="noreferrer">
                                    <img src="<?php echo e($photo); ?>" alt="<?php echo e($course->name); ?> picture from UDisc" loading="lazy">
                                </a>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </main>
        </div>
    </body>
</html><?php /**PATH /var/www/yelgolf/resources/views/courses/show.blade.php ENDPATH**/ ?>