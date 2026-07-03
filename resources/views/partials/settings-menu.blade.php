@php
    $variant = $variant ?? 'button';
    $isActive = $isActive ?? false;
    $linkClass = $variant === 'nav'
        ? 'settings-link settings-link--nav'.($isActive ? ' sports-nav__link sports-nav__link--active' : ' sports-nav__link')
        : 'button button-secondary settings-link';
@endphp

<a class="{{ $linkClass }}" href="{{ route('settings.edit') }}" @if($isActive) aria-current="page" @endif>{{ __('ui.settings.button') }}</a>