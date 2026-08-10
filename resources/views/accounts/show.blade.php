@extends('layouts.app')
@section('title', $account->label())

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.accounts.index', $group) }}">{{ __('accounts.show.breadcrumb') }}</a></p>
            <h1>{{ $account->label() }}</h1>
            <p class="muted small">
                {{ $account->type }}
                · {{ __('accounts.show.balance_prefix') }} <x-amount :value="$balance" :currency="$currency" signed />
                @if ($account->requires_cost_center) · {{ __('accounts.show.cost_center_required') }} @endif
            </p>
        </div>
    </div>

    @if ($nativeBalances->isNotEmpty())
        <div class="card">
            <h3>{{ __('accounts.show.native_balances_heading') }}</h3>
            <div class="actions">
                @foreach ($nativeBalances as $currencyCode => $amount)
                    <span class="badge"><x-amount :value="$amount" :currency="$currencyCode" signed /></span>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="filters card">
        <div class="field">
            <label for="from">{{ __('accounts.show.filter_from_label') }}</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">{{ __('accounts.show.filter_to_label') }}</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
        </div>
        <button class="btn">{{ __('accounts.show.filter_submit') }}</button>
    </form>

    <div class="card">
        <h2>{{ __('accounts.show.ledger_heading') }}</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('accounts.show.col_date') }}</th>
                    <th>{{ __('accounts.show.col_entry') }}</th>
                    <th>{{ __('accounts.show.col_description') }}</th>
                    <th>{{ __('accounts.show.col_cost_center') }}</th>
                    <th class="num">{{ __('accounts.show.col_debit') }}</th>
                    <th class="num">{{ __('accounts.show.col_credit') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td class="num"><x-datetime :value="$line->journalEntry->entry_date" /></td>
                        <td class="mono">
                            <a href="{{ route('g.ledger.show', [$group, $line->journalEntry]) }}">
                                {{ $line->journalEntry->entry_number }}
                            </a>
                        </td>
                        <td>
                            {{ $line->description ?? $line->journalEntry->description }}
                            @if ($line->journalEntry->isReversed())
                                <span class="badge badge-danger">{{ __('accounts.show.reversed_badge') }}</span>
                            @endif
                        </td>
                        <td class="small">
                            @if ($line->costCenter)
                                <a href="{{ route('g.cost-centers.show', [$group, $line->costCenter]) }}">{{ $line->costCenter->code }}</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="num">@if ($line->isDebit())<x-amount :value="$line->debit" :currency="$line->currency" />@endif</td>
                        <td class="num">@if (! $line->isDebit())<x-amount :value="$line->credit" :currency="$line->currency" />@endif</td>
                    </tr>
                @empty
                    <x-empty colspan="6">{{ __('accounts.show.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
