<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title><?php echo e($title ? $title.' | '.config('app.name') : config('app.name')); ?></title>
        <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
        <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    </head>
    <body>
        <div class="toast-shell" data-toast-shell hidden>
            <div class="toast" data-toast>
                <div class="toast__status" data-toast-status></div>
                <p class="toast__message" data-toast-message></p>
            </div>
        </div>

        <?php echo e($slot); ?>

        <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

        <script>
            (() => {
                const shell = document.querySelector('[data-toast-shell]');
                const toast = document.querySelector('[data-toast]');
                const message = document.querySelector('[data-toast-message]');
                const status = document.querySelector('[data-toast-status]');

                if (!shell || !toast || !message || !status) {
                    return;
                }

                let activeTimer = null;

                const hideToast = () => {
                    shell.hidden = true;
                    toast.dataset.state = 'hidden';
                };

                window.addEventListener('notify', (event) => {
                    const detail = event.detail || {};
                    message.textContent = detail.message || 'Done';
                    status.textContent = detail.type === 'error' ? 'Error' : 'Updated';
                    toast.dataset.type = detail.type || 'success';
                    toast.dataset.state = 'visible';
                    shell.hidden = false;

                    window.clearTimeout(activeTimer);
                    activeTimer = window.setTimeout(hideToast, 2600);
                });
            })();
        </script>
    </body>
</html><?php /**PATH /var/www/yelgolf/resources/views/layouts/app.blade.php ENDPATH**/ ?>