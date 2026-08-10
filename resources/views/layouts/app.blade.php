<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('locales.available.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', __('nav.default_title'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
{{--
    Navigation is not written in this file. $navigation is resolved per request
    by App\Domain\Access\NavigationBuilder from the surface declarations in
    App\Domain\Access\AccessMap, so giving an access level a new destination
    means adding one NavItem there — this layout stays generic over however many
    surfaces exist and never asks what role the viewer holds.

    The sidebar below is a mobile off-canvas drawer by default, toggled by the
    hidden checkbox/label pair immediately below (pure CSS, no script) and
    becomes a permanent column at the desktop breakpoint — see public/css/app.css.
--}}
<input type="checkbox" id="nav-toggle" class="nav-toggle-input">

<header class="masthead">
    <div class="masthead-inner">
        <label for="nav-toggle" class="nav-toggle-btn" aria-label="{{ __('nav.menu') }}">
            <x-icon name="menu" />
        </label>
        <a class="brand" href="{{ route('home') }}">{{ __('nav.brand') }}</a>
        <a class="masthead-avatar" href="{{ route('p.home') }}" aria-label="{{ auth()->user()?->name }}">
            <x-avatar :name="auth()->user()?->name" size="sm" />
        </a>
    </div>
</header>

<label for="nav-toggle" class="nav-scrim" aria-hidden="true"></label>

<nav class="sidebar" aria-label="{{ __('nav.sections.aria_label') }}">
    <div class="sidebar-inner">
        <a class="sidebar-brand" href="{{ route('home') }}">{{ __('nav.brand') }}</a>

        @if (($navigation ?? null)?->hasSwitches())
            {{--
                The surface switcher. A fund administrator is also an investor, and a
                system administrator may be one too, so the levels never collapse into
                one another: this moves between whole experiences rather than revealing
                extra links inside a single shared one.
            --}}
            <div class="sidebar-switches" aria-label="{{ __('nav.surfaces.aria_label') }}">
                @foreach ($navigation->switches as $switch)
                    <a href="{{ $switch->href }}" @if ($switch->current) aria-current="true" @endif>
                        <x-icon :name="$switch->icon ?? 'layers'" />
                        {{ __($switch->label) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (($navigation ?? null)?->has())
            @foreach ($navigation->sections as $section)
                <div class="sidebar-group">
                    @if ($section->isLabelled())
                        <p class="sidebar-caption">{{ __($section->label) }}</p>
                    @endif
                    @foreach ($section->items as $item)
                        <a href="{{ $navigation->href($item) }}"
                           @if ($item->isCurrent()) aria-current="page" @endif>
                            <x-icon :name="$item->icon ?? 'dot'" />
                            {{ __($item->label) }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        @endif

        <div class="sidebar-footer">
            <a href="{{ route('exchange-rates.index') }}">
                <x-icon name="exchange" /> {{ __('nav.exchange_rates') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-link">
                    <x-icon name="exit" /> {{ __('nav.sign_out') }}
                </button>
            </form>
        </div>
    </div>
</nav>

<main>
    <div class="container">
        <x-alerts />
        @yield('content')
    </div>
</main>
</body>
</html>
