@php
    $coursesIsActive = request()->routeIs('home') || request()->routeIs('courses.show');
    $sessionsIsActive = request()->routeIs('sessions.*');
    $isAdmin = $isAdminAuthenticated ?? false;
    $accountIsActive = $isAdmin
        ? request()->routeIs('admin.*')
        : request()->routeIs('login');
    $settingsIsActive = request()->routeIs('settings.edit');
    $navLinkClass = static fn (bool $isActive): string => 'sports-nav__link'.($isActive ? ' sports-nav__link--active' : '');
@endphp

<nav class="sports-nav" aria-label="Primary">
    <a class="{{ $navLinkClass($coursesIsActive) }}" href="{{ url('/').'#course-list' }}" @if($coursesIsActive) aria-current="page" @endif>{{ __('ui.nav.courses') }}</a>

    @if ($currentPlayer ?? false)
        <a class="{{ $navLinkClass($sessionsIsActive) }}" href="{{ route('sessions.index') }}" @if($sessionsIsActive) aria-current="page" @endif>{{ __('ui.nav.sessions') }}</a>
    @endif

    @if ($isAdmin)
        <a class="{{ $navLinkClass($accountIsActive) }}" href="{{ route('admin.dashboard') }}" @if($accountIsActive) aria-current="page" @endif>{{ __('ui.nav.admin') }}</a>
    @elseif ($currentPlayer ?? false)
        <span class="sports-nav__link sports-nav__link--active">{{ $currentPlayer->name }}</span>
        <form class="logout-form logout-form--nav" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sports-nav__link sports-nav__button" type="submit">{{ __('ui.nav.logout') }}</button>
        </form>
    @else
        <a class="{{ $navLinkClass($accountIsActive) }}" href="{{ route('login') }}" @if($accountIsActive) aria-current="page" @endif>{{ __('ui.nav.login') }}</a>
    @endif

    @if ($isAdmin)
        @include('partials.settings-menu', ['variant' => 'nav', 'isActive' => $settingsIsActive])

        <form class="logout-form logout-form--nav" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sports-nav__link sports-nav__button" type="submit">{{ __('ui.nav.logout') }}</button>
        </form>
    @endif
</nav>
