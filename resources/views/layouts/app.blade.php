<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('locales.available.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('nav.default_title'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="masthead">
    <div class="masthead-inner">
        <a class="brand" href="{{ route('home') }}">{{ __('nav.brand') }}</a>
        <nav class="masthead-actions">
            <a href="{{ route('exchange-rates.index') }}">{{ __('nav.exchange_rates') }}</a>
            @if (auth()->user()?->isSystemAdmin())
                <a href="{{ route('admin.dashboard') }}">{{ __('nav.site_settings') }}</a>
            @endif
            <a href="{{ route('p.home') }}">{{ auth()->user()->name }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-link">{{ __('nav.sign_out') }}</button>
            </form>
        </nav>
    </div>
</header>

@isset($group)
    <nav class="groupnav">
        <ul>
            @php($nav = [
                'g.dashboard' => [__('nav.links.dashboard'), []],
                'g.transactions.index' => [__('nav.links.activity'), []],
                'g.loans.index' => [__('nav.links.loans'), []],
                'g.treasuries.index' => [__('nav.links.treasuries'), []],
                'g.wallets.index' => [__('nav.links.wallets'), []],
                'g.members.index' => [__('nav.links.members'), []],
                'g.policies.index' => [__('nav.links.policies'), []],
                'g.ledger.index' => [__('nav.links.ledger'), []],
                'g.accounts.index' => [__('nav.links.accounts'), []],
                'g.cost-centers.index' => [__('nav.links.cost_centers'), []],
                'g.reports.index' => [__('nav.links.reports'), []],
            ])
            @foreach ($nav as $route => [$label, $params])
                <li>
                    <a href="{{ route($route, [$group] + $params) }}"
                       @if (request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route))) aria-current="page" @endif>
                        {{ $label }}
                    </a>
                </li>
            @endforeach
            @if (($groupContext ?? null)?->isAdmin())
                <li>
                    <a href="{{ route('g.periods.index', $group) }}"
                       @if (request()->routeIs('g.periods.*')) aria-current="page" @endif>{{ __('nav.links.periods') }}</a>
                </li>
                <li>
                    <a href="{{ route('g.audit.index', $group) }}"
                       @if (request()->routeIs('g.audit.*')) aria-current="page" @endif>{{ __('nav.links.audit') }}</a>
                </li>
            @endif
        </ul>
    </nav>
@endisset

<main>
    <div class="container">
        <x-alerts />
        @yield('content')
    </div>
</main>
</body>
</html>
