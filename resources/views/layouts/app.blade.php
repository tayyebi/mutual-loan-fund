<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mutual Loan Fund')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="masthead">
    <div class="masthead-inner">
        <a class="brand" href="{{ route('home') }}">Mutual Loan Fund</a>
        <nav class="masthead-actions">
            <a href="{{ route('exchange-rates.index') }}">Exchange rates</a>
            <span class="muted">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-link">Sign out</button>
            </form>
        </nav>
    </div>
</header>

@isset($group)
    <nav class="groupnav">
        <ul>
            @php($nav = [
                'g.dashboard' => ['Dashboard', []],
                'g.transactions.index' => ['Activity', []],
                'g.loans.index' => ['Loans', []],
                'g.treasuries.index' => ['Treasuries', []],
                'g.wallets.index' => ['Wallets', []],
                'g.members.index' => ['Members', []],
                'g.policies.index' => ['Policies', []],
                'g.ledger.index' => ['Ledger', []],
                'g.accounts.index' => ['Accounts', []],
                'g.cost-centers.index' => ['Cost centers', []],
                'g.reports.index' => ['Reports', []],
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
                       @if (request()->routeIs('g.periods.*')) aria-current="page" @endif>Periods</a>
                </li>
                <li>
                    <a href="{{ route('g.audit.index', $group) }}"
                       @if (request()->routeIs('g.audit.*')) aria-current="page" @endif>Audit</a>
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
