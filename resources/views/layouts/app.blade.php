<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>
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
</html>