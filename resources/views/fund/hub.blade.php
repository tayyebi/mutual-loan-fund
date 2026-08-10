@extends('layouts.app')
@section('title', __('fund.hub.title', ['fund' => $group->name]))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('fund.hub.heading', ['fund' => $group->name]) }}</h1>
            <p class="muted small">
                @if ($policy)
                    {{ __('fund.hub.governed_by', ['version' => $policy->version]) }}
                    <a href="{{ route('g.policies.show', [$group, $policy->version]) }}">v{{ $policy->version }}</a>,
                    {{ __('fund.hub.active_since') }} <x-datetime :value="$policy->effective_from" />.
                @else
                    {{ __('fund.hub.no_active_policy') }}
                @endif
            </p>
        </div>
        <a class="btn" href="{{ route('u.dashboard', $group) }}">{{ __('fund.hub.my_own_position') }}</a>
    </div>

    {{--
        An administrator arriving here is almost always arriving to clear a
        queue, so the work comes first and the valuation second.
    --}}
    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('fund.hub.awaiting_verification') }}</p>
            <p class="figure">{{ $queues['transactions'] }}</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('g.transactions.index', [$group, 'status' => 'pending']) }}">{{ __('fund.hub.review_transactions') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('fund.hub.join_requests') }}</p>
            <p class="figure">{{ $queues['joins'] }}</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('g.members.requests', $group) }}">{{ __('fund.hub.review_requests') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('fund.hub.loans_to_decide') }}</p>
            <p class="figure">{{ $queues['loans'] }}</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('g.loans.index', $group) }}">{{ __('fund.hub.review_loans') }}</a>
            </p>
        </div>
    </div>

    @if ($draft)
        <div class="alert alert-info">
            {{ __('fund.hub.draft_open', ['version' => $draft->version]) }}
            <a href="{{ route('g.policies.edit', [$group, $draft->version]) }}">{{ __('fund.hub.open_draft') }}</a>
        </div>
    @endif

    <div class="grid grid-side" style="margin-top:1rem">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>{{ __('fund.hub.treasuries') }}</h2>
                    <a class="small" href="{{ route('g.treasuries.index', $group) }}">{{ __('fund.hub.manage') }}</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('fund.hub.treasury') }}</th>
                            <th class="num">{{ __('fund.hub.balance') }}</th>
                            <th class="num">{{ __('fund.hub.approx_gold') }}</th>
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
                                        <span class="muted">{{ __('fund.hub.no_rate') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty colspan="3">{{ __('fund.hub.no_treasuries') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2>{{ __('fund.hub.recent_activity') }}</h2>
                    <a class="small" href="{{ route('g.transactions.index', $group) }}">{{ __('fund.hub.all_activity') }}</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('fund.hub.date') }}</th>
                            <th>{{ __('fund.hub.member') }}</th>
                            <th class="num">{{ __('fund.hub.amount') }}</th>
                            <th>{{ __('fund.hub.type') }}</th>
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
                            <x-empty colspan="4">{{ __('fund.hub.nothing_posted') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('fund.hub.fund_value') }}</h2>
            <p class="figure">{{ $summary['gold']->format(4) }}</p>
            <p class="small muted">{{ __('fund.hub.gold_grams', ['unit' => $goldUnit]) }}</p>
            @if ($summary['unvalued_lines'] > 0)
                <p class="small muted">{{ __('fund.hub.unvalued_lines', ['count' => $summary['unvalued_lines']]) }}</p>
            @endif

            <dl class="deflist" style="margin-top:0.8rem">
                @foreach ($summary['outstanding_loans'] as $currency => $amount)
                    <dt>{{ __('fund.hub.lent_out') }}</dt>
                    <dd><x-amount :value="$amount" :currency="$currency" /></dd>
                @endforeach
            </dl>

            <p class="small muted" style="margin:0.8rem 0 0">
                <a href="{{ route('g.reports.index', $group) }}">{{ __('fund.hub.all_reports') }}</a>
            </p>
        </div>
    </div>
@endsection
