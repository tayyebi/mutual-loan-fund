<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('locales.available.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
--}}
<header class="masthead">
    <div class="masthead-inner">
        <a class="brand" href="{{ route('home') }}">{{ __('nav.brand') }}</a>
        <nav class="masthead-actions">
            <a href="{{ route('exchange-rates.index') }}">{{ __('nav.exchange_rates') }}</a>
            <a href="{{ route('p.home') }}">{{ auth()->user()?->name }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-link">{{ __('nav.sign_out') }}</button>
            </form>
        </nav>
    </div>
</header>

@if (($navigation ?? null)?->hasSwitches())
    {{--
        The surface switcher. A fund administrator is also an investor, and a
        system administrator may be one too, so the levels never collapse into
        one another: this moves between whole experiences rather than revealing
        extra links inside a single shared one.
    --}}
    <nav class="surfaces" aria-label="{{ __('nav.surfaces.aria_label') }}">
        <div class="surfaces-inner">
            <ul>
                @foreach ($navigation->switches as $switch)
                    <li>
                        <a href="{{ $switch->href }}"
                           @if ($switch->current) aria-current="true" @endif>{{ __($switch->label) }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif

@if (($navigation ?? null)?->has())
    <nav class="groupnav" aria-label="{{ __('nav.sections.aria_label') }}">
        <ul>
            @foreach ($navigation->sections as $section)
                @if ($section->isLabelled())
                    <li class="navgroup">{{ __($section->label) }}</li>
                @endif
                @foreach ($section->items as $item)
                    <li>
                        <a href="{{ $navigation->href($item) }}"
                           @if ($item->isCurrent()) aria-current="page" @endif>{{ __($item->label) }}</a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </nav>
@endif

<main>
    <div class="container">
        <x-alerts />
        @yield('content')
    </div>
</main>
</body>
</html>
