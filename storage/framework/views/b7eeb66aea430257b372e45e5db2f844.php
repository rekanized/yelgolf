<div class="auth-wrap">
    <section class="auth-card">
        <p class="eyebrow">Admin access</p>
        <h1>Sign in</h1>
        <p class="lead">Temporary credentials are <strong>test</strong> / <strong>test</strong>. Google auth can replace this later.</p>

        <form wire:submit="authenticate">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" wire:model="username" autocomplete="username">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="error-text"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" wire:model="password" autocomplete="current-password">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="error-text"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="actions" style="margin-top: 22px;">
                <button class="button button-primary button-with-spinner" type="submit" wire:loading.attr="disabled" wire:target="authenticate">
                    <span wire:loading.remove wire:target="authenticate">Log in</span>
                    <span class="button-spinner-wrap" wire:loading wire:target="authenticate">
                        <span class="button-spinner" aria-hidden="true"></span>
                        Signing in...
                    </span>
                </button>
                <a class="button button-secondary" href="<?php echo e(url('/')); ?>">Back to courses</a>
            </div>
        </form>
    </section>
</div><?php /**PATH /var/www/yelgolf/resources/views/livewire/admin/login-form.blade.php ENDPATH**/ ?>