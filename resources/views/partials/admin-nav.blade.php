@php
    $adminNavLinkClass = static fn (bool $isActive): string => 'button '.($isActive ? 'button-primary' : 'button-secondary');
@endphp

<nav class="admin-nav" aria-label="{{ __('ui.admin.navigation') }}">
    <a class="{{ $adminNavLinkClass(request()->routeIs('admin.dashboard')) }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
        {{ __('ui.admin.nav_courses') }}
    </a>
    <a class="{{ $adminNavLinkClass(request()->routeIs('admin.users')) }}" href="{{ route('admin.users') }}" @if(request()->routeIs('admin.users')) aria-current="page" @endif>
        {{ __('ui.admin.nav_users') }}
    </a>
</nav>
