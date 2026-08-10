@extends('layouts.app')
@section('title', $group->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $group->name }}</h1>
            <p class="muted small">
                @if ($policy)
                    {{ __('dashboard.governed_by_policy') }} <a href="{{ route('g.policies.show', [$group, $policy->version]) }}">v{{ $policy->version }}</a>,
                    {{ __('dashboard.active_since') }} <x-datetime :value="$policy->effective_from" />.
                @else
                    {{ __('dashboard.no_active_policy') }}
                @endif
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('g.transactions.create', $group) }}">{{ __('dashboard.contribute') }}</a>
            <a class="btn" href="{{ route('g.loans.create', $group) }}">{{ __('dashboard.request_loan') }}</a>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('dashboard.fund_value') }}</p>
            <p class="figure">{{ $summary['gold']->format(4) }}</p>
            <p class="small muted">{{ __('dashboard.gold_grams', ['unit' => $goldUnit]) }}</p>
            @if ($summary['unvalued_lines'] > 0)
                <p class="small muted" style="margin:0">
                    {{ __('dashboard.unvalued_lines', ['count' => $summary['unvalued_lines']]) }}
                </p>
            @endif
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('dashboard.loans_outstanding') }}</p>
            @forelse ($summary['outstanding_loans'] as $currency => $amount)
                <p class="figure" style="font-size:1.4rem"><x-amount :value="$amount" :currency="$currency" /></p>
            @empty
                <p class="figure" style="font-size:1.4rem">{{ __('dashboard.none') }}</p>
            @endforelse
            <p class="small muted" style="margin:0">
                <a href="{{ route('g.reports.show', [$group, 'receivables']) }}">{{ __('dashboard.receivables_by_member') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('dashboard.awaiting_verification') }}</p>
            <p class="figure">{{ $summary['pending'] }}</p>
            <p class="small muted" style="margin:0">
                <a href="{{ route('g.transactions.index', [$group, 'status' => 'pending']) }}">{{ __('dashboard.pending_transactions') }}</a>
            </p>
        </div>
    </div>

    <div class="grid grid-side" style="margin-top: 1rem;">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>{{ __('dashboard.treasuries') }}</h2>
                    <a class="small" href="{{ route('g.treasuries.index', $group) }}">{{ __('dashboard.manage') }}</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('dashboard.treasury') }}</th>
                            <th class="num">{{ __('dashboard.balance') }}</th>
                            <th class="num">{{ __('dashboard.approx_gold') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($summary['treasuries'] as $row)
                            <tr>
                                <td>
                                    {{ $row['treasury']->name }}
                                    <span class="badge">{{ $row['treasury']->type }}</span>
                                </td>
                                <td class="num"><x-amount :value="$row['balance']" /></td>
                                <td class="num">
                                    @if ($row['gold'])
                                        {{ $row['gold']->format(4) }} g
                                    @else
                                        <span class="muted">{{ __('dashboard.no_rate') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty colspan="3">{{ __('dashboard.no_treasuries') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2>{{ __('dashboard.recent_activity') }}</h2>
                    <a class="small" href="{{ route('g.transactions.index', $group) }}">{{ __('dashboard.all_activity') }}</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('dashboard.date') }}</th>
                            <th>{{ __('dashboard.member') }}</th>
                            <th class="num">{{ __('dashboard.amount') }}</th>
                            <th>{{ __('dashboard.type') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($summary['activity'] as $transaction)
                            <tr>
                                <td class="num"><x-datetime :value="$transaction->occurred_on" format="j M" /></td>
                                <td>{{ $transaction->member?->displayName() ?? '—' }}</td>
                                <td class="num">
                                    <a href="{{ route('g.transactions.show', [$group, $transaction]) }}">
                                        <x-amount :value="$transaction->amount" :currency="$transaction->currency" />
                                    </a>
                                </td>
                                <td class="small muted">{{ $transaction->typeLabel() }}</td>
                            </tr>
                        @empty
                            <x-empty colspan="4">{{ __('dashboard.nothing_posted') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('dashboard.your_position') }}</h2>
            <dl class="deflist">
                <dt>{{ __('dashboard.contributed') }}</dt>
                <dd><x-amount :value="$position['contributed']" :currency="$position['currency']" /></dd>

                <dt>{{ __('dashboard.outstanding') }}</dt>
                <dd><x-amount :value="$position['outstanding']" :currency="$position['currency']" /></dd>

                <dt>{{ __('dashboard.interest_paid') }}</dt>
                <dd><x-amount :value="$position['interest_paid']" :currency="$position['currency']" /></dd>

                @if ($position['gold'])
                    <dt>{{ __('dashboard.approx_gold') }}</dt>
                    <dd class="num">{{ $position['gold']->format(4) }} g</dd>
                @endif
            </dl>
            <p class="small muted" style="margin-top:0.8rem">
                {{ __('dashboard.calculated_note') }}
            </p>
        </div>
    </div>
@endsection
