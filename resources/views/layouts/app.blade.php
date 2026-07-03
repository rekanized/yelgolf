<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $currentTheme ?? config('yelgolf.default_theme', 'dark') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        @livewireStyles
    </head>
    <body>
        <div class="toast-shell" data-toast-shell hidden>
            <div class="toast" data-toast>
                <div class="toast__status" data-toast-status></div>
                <p class="toast__message" data-toast-message></p>
            </div>
        </div>

        {{ $slot }}
        @livewireScripts
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
                    message.textContent = detail.message || @js(__('ui.toast.done'));
                    status.textContent = detail.type === 'error' ? @js(__('ui.toast.error')) : @js(__('ui.toast.updated'));
                    toast.dataset.type = detail.type || 'success';
                    toast.dataset.state = 'visible';
                    shell.hidden = false;

                    window.clearTimeout(activeTimer);
                    activeTimer = window.setTimeout(hideToast, 2600);
                });
            })();
        </script>
    </body>
</html>