@extends('layouts.app')
@section('title', $group->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $group->name }}</h1>
            <p class="muted small">
                @if ($policy)
                    Governed by policy <a href="{{ route('g.policies.show', [$group, $policy->version]) }}">v{{ $policy->version }}</a>,
                    active since {{ $policy->effective_from?->format('j M Y') }}.
                @else
                    No active policy. Financial operations are refused until one is published.
                @endif
            </p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('g.transactions.create', $group) }}">Contribute</a>
            <a class="btn" href="{{ route('g.loans.create', $group) }}">Request a loan</a>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">Fund value</p>
            <p class="figure">{{ $summary['gold']->format(4) }}</p>
            <p class="small muted">grams of 18K gold ({{ $goldUnit }})</p>
            @if ($summary['unvalued_lines'] > 0)
                <p class="small muted" style="margin:0">
                    {{ $summary['unvalued_lines'] }} posted lines have no gold valuation:
                    no rate existed for their currency when they were posted.
                </p>
            @endif
        </div>

        <div class="card">
            <p class="figure-sub">Loans outstanding</p>
            @forelse ($summary['outstanding_loans'] as $currency => $amount)
                <p class="figure" style="font-size:1.4rem"><x-amount :value="$amount" :currency="$currency" /></p>
            @empty
                <p class="figure" style="font-size:1.4rem">None</p>
            @endforelse
            <p class="small muted" style="margin:0">
                <a href="{{ route('g.reports.show', [$group, 'receivables']) }}">Receivables by member</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">Awaiting verification</p>
            <p class="figure">{{ $summary['pending'] }}</p>
            <p class="small muted" style="margin:0">
                <a href="{{ route('g.transactions.index', [$group, 'status' => 'pending']) }}">Pending transactions</a>
            </p>
        </div>
    </div>

    <div class="grid grid-side" style="margin-top: 1rem;">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>Treasuries</h2>
                    <a class="small" href="{{ route('g.treasuries.index', $group) }}">Manage</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Treasury</th>
                            <th class="num">Balance</th>
                            <th class="num">≈ 18K gold</th>
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
                                        <span class="muted">no rate</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty colspan="3">No treasuries yet. An administrator creates the first one.</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2>Recent activity</h2>
                    <a class="small" href="{{ route('g.transactions.index', $group) }}">All activity</a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Member</th>
                            <th class="num">Amount</th>
                            <th>Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($summary['activity'] as $transaction)
                            <tr>
                                <td class="num">{{ $transaction->occurred_on->format('j M') }}</td>
                                <td>{{ $transaction->member?->displayName() ?? '—' }}</td>
                                <td class="num">
                                    <a href="{{ route('g.transactions.show', [$group, $transaction]) }}">
                                        <x-amount :value="$transaction->amount" :currency="$transaction->currency" />
                                    </a>
                                </td>
                                <td class="small muted">{{ $transaction->typeLabel() }}</td>
                            </tr>
                        @empty
                            <x-empty colspan="4">Nothing has been posted yet.</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Your position</h2>
            <dl class="deflist">
                <dt>Contributed</dt>
                <dd><x-amount :value="$position['contributed']" :currency="$position['currency']" /></dd>

                <dt>Outstanding</dt>
                <dd><x-amount :value="$position['outstanding']" :currency="$position['currency']" /></dd>

                <dt>Interest paid</dt>
                <dd><x-amount :value="$position['interest_paid']" :currency="$position['currency']" /></dd>

                @if ($position['gold'])
                    <dt>≈ 18K gold</dt>
                    <dd class="num">{{ $position['gold']->format(4) }} g</dd>
                @endif
            </dl>
            <p class="small muted" style="margin-top:0.8rem">
                Calculated from the ledger, not stored as a balance.
            </p>
        </div>
    </div>
@endsection
