<div class="admin-shell">
    <header class="topbar">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Course importer</h1>
        </div>

        <div class="actions">
            <a class="button button-secondary" href="<?php echo e(url('/')); ?>">View courses</a>
            <form class="logout-form" method="POST" action="<?php echo e(route('admin.logout')); ?>">
                <?php echo csrf_field(); ?>
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
                <div class="badge"><?php echo e($courses->count()); ?> imported</div>
            </div>

            <form class="import-form" wire:submit="importCourse">
                <div class="field">
                    <label for="udisc-url">UDisc URL</label>
                    <input id="udisc-url" type="url" wire:model="udiscUrl" placeholder="https://udisc.com/courses/haesthagen-M8Wu">
                    <p class="field-help">Only public UDisc course pages are supported.</p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['udiscUrl'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="error-text"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($courses->isEmpty()): ?>
            <div class="empty-state">
                <h3>No courses yet</h3>
                <p class="muted">Import your first UDisc course to start building the directory.</p>
            </div>
        <?php else: ?>
            <div class="course-list">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <article class="course-list-item">
                        <div class="dashboard-header">
                            <div>
                                <h3><?php echo e($course->name); ?></h3>
                                <p class="muted"><?php echo e($course->location_name ?? 'Location unavailable'); ?></p>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($course->rating): ?>
                                <div class="badge"><?php echo e(number_format((float) $course->rating, 1)); ?> / 5</div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <p class="muted"><?php echo e($course->description ?: 'No course description was found during import.'); ?></p>

                        <div class="course-admin-footer">
                            <div class="course-admin-footer__stats">
                                <span class="muted">Holes: <?php echo e($course->holes_count ?? 'Unknown'); ?></span>
                                <span class="muted">Established: <?php echo e($course->established_year ?? 'Unknown'); ?></span>
                                <span class="muted">Difficulty: <?php echo e($course->difficulty_levels ? implode(', ', array_map(static fn (string $value): string => \Illuminate\Support\Str::headline($value), $course->difficulty_levels)) : 'Unknown'); ?></span>
                                <span class="muted">Layouts: <?php echo e($course->holes->groupBy('layout_order')->count()); ?></span>
                            </div>
                            <div class="course-admin-footer__actions">
                                <button class="button button-secondary button-with-spinner" type="button" wire:click="updateCourse(<?php echo e($course->id); ?>)" wire:loading.attr="disabled" wire:target="updateCourse(<?php echo e($course->id); ?>)">
                                    <span wire:loading.remove wire:target="updateCourse(<?php echo e($course->id); ?>)">Update</span>
                                    <span class="button-spinner-wrap" wire:loading wire:target="updateCourse(<?php echo e($course->id); ?>)">
                                        <span class="button-spinner" aria-hidden="true"></span>
                                        Updating...
                                    </span>
                                </button>
                                <a class="text-link" href="<?php echo e($course->udisc_url); ?>" target="_blank" rel="noreferrer">Open source</a>
                            </div>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </section>
</div><?php /**PATH /var/www/yelgolf/resources/views/livewire/admin/course-manager.blade.php ENDPATH**/ ?>