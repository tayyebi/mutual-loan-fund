@extends('layouts.app')
@section('title', $member->displayName())

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.members.index', $group) }}">{{ __('members.show.breadcrumb') }}</a></p>
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
                <h2>{{ __('members.show.position_heading') }}</h2>
                <p class="small muted">{{ __('members.show.position_intro') }}</p>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('members.show.account_header') }}</th>
                            <th class="num">{{ __('members.show.debit_header') }}</th>
                            <th class="num">{{ __('members.show.credit_header') }}</th>
                            <th class="num">{{ __('members.show.balance_header', ['currency' => $position['currency']]) }}</th>
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
                            <x-empty colspan="4">{{ __('members.show.no_activity') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>{{ __('members.show.loans_heading') }}</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('members.show.reference_header') }}</th>
                            <th class="num">{{ __('members.show.principal_header') }}</th>
                            <th>{{ __('members.show.policy_header') }}</th>
                            <th>{{ __('members.show.status_header') }}</th>
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
                            <x-empty colspan="4">{{ __('members.show.no_loans') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <h3>{{ __('members.show.summary_heading') }}</h3>
                <dl class="deflist">
                    <dt>{{ __('members.show.contributed') }}</dt>
                    <dd><x-amount :value="$position['contributed']" :currency="$position['currency']" /></dd>
                    <dt>{{ __('members.show.outstanding') }}</dt>
                    <dd><x-amount :value="$position['outstanding']" :currency="$position['currency']" /></dd>
                    <dt>{{ __('members.show.interest_paid') }}</dt>
                    <dd><x-amount :value="$position['interest_paid']" :currency="$position['currency']" /></dd>
                    <dt>{{ __('members.show.member_for') }}</dt>
                    <dd>{{ __('members.show.days_count', ['days' => $member->membershipDays()]) }}</dd>
                </dl>
            </div>

            <div class="card">
                <h3>{{ __('members.show.wallets_heading') }}</h3>
                @forelse ($member->wallets as $wallet)
                    <p class="small" style="margin-bottom:0.4rem">
                        <span class="badge">{{ $wallet->network }}</span>
                        <span class="mono">{{ $wallet->maskedAddress() }}</span>
                        <br><span class="muted">{{ $wallet->label ?? $wallet->currency }}</span>
                    </p>
                @empty
                    <p class="small muted">{{ __('members.show.no_wallets') }}</p>
                @endforelse
                <p class="small muted" style="margin:0">
                    {{ __('members.show.wallets_note') }}
                </p>
            </div>
        </div>
    </div>
@endsection
