@extends('layouts.app')
@section('title', $member->displayName())

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.members.index', $group) }}">Members</a></p>
            <h1>{{ $member->displayName() }}</h1>
            <p class="muted small">
                {{ $member->role }} · <x-status :value="$member->status" />
                @if ($member->costCenter)
                    · <a href="{{ route('g.cost-centers.show', [$group, $member->costCenter]) }}">{{ $member->costCenter->code }}</a>
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <h2>Position</h2>
                <p class="small muted">Every figure below is derived from posted journal lines.</p>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Account</th>
                            <th class="num">Debit</th>
                            <th class="num">Credit</th>
                            <th class="num">Balance ({{ $position['currency'] }})</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($position['statement'] as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('g.accounts.show', [$group, $row['account']]) }}">
                                        {{ $row['account']->label() }}
                                    </a>
                                </td>
                                <td class="num"><x-amount :value="$row['debit']" /></td>
                                <td class="num"><x-amount :value="$row['credit']" /></td>
                                <td class="num"><x-amount :value="$row['balance']" signed /></td>
                            </tr>
                        @empty
                            <x-empty colspan="4">No activity attributed to this member yet.</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>Loans</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Reference</th>
                            <th class="num">Principal</th>
                            <th>Policy</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($loans as $loan)
                            <tr>
                                <td><a href="{{ route('g.loans.show', [$group, $loan]) }}">{{ $loan->reference }}</a></td>
                                <td class="num"><x-amount :value="$loan->principal" :currency="$loan->currency" /></td>
                                <td class="small muted">v{{ $loan->policyVersion?->version }}</td>
                                <td><x-status :value="$loan->status" /></td>
                            </tr>
                        @empty
                            <x-empty colspan="4">No loans.</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <h3>Summary</h3>
                <dl class="deflist">
                    <dt>Contributed</dt>
                    <dd><x-amount :value="$position['contributed']" :currency="$position['currency']" /></dd>
                    <dt>Outstanding</dt>
                    <dd><x-amount :value="$position['outstanding']" :currency="$position['currency']" /></dd>
                    <dt>Interest paid</dt>
                    <dd><x-amount :value="$position['interest_paid']" :currency="$position['currency']" /></dd>
                    <dt>Member for</dt>
                    <dd>{{ $member->membershipDays() }} days</dd>
                </dl>
            </div>

            <div class="card">
                <h3>Wallets</h3>
                @forelse ($member->wallets as $wallet)
                    <p class="small" style="margin-bottom:0.4rem">
                        <span class="badge">{{ $wallet->network }}</span>
                        <span class="mono">{{ $wallet->maskedAddress() }}</span>
                        <br><span class="muted">{{ $wallet->label ?? $wallet->currency }}</span>
                    </p>
                @empty
                    <p class="small muted">No registered wallets.</p>
                @endforelse
                <p class="small muted" style="margin:0">
                    The application never stores keys or seed phrases.
                </p>
            </div>
        </div>
    </div>
@endsection
