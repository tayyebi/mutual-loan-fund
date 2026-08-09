@extends('layouts.app')
@section('title', $account->label())

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.accounts.index', $group) }}">Chart of accounts</a></p>
            <h1>{{ $account->label() }}</h1>
            <p class="muted small">
                {{ $account->type }}
                · balance <x-amount :value="$balance" :currency="$currency" signed />
                @if ($account->requires_cost_center) · cost center required @endif
            </p>
        </div>
    </div>

    @if ($nativeBalances->isNotEmpty())
        <div class="card">
            <h3>Native balances</h3>
            <div class="actions">
                @foreach ($nativeBalances as $currencyCode => $amount)
                    <span class="badge"><x-amount :value="$amount" :currency="$currencyCode" signed /></span>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="filters card">
        <div class="field">
            <label for="from">From</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">To</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
        </div>
        <button class="btn">Filter</button>
    </form>

    <div class="card">
        <h2>General ledger</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Entry</th>
                    <th>Description</th>
                    <th>Cost center</th>
                    <th class="num">Debit</th>
                    <th class="num">Credit</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td class="num">{{ $line->journalEntry->entry_date->format('j M Y') }}</td>
                        <td class="mono">
                            <a href="{{ route('g.ledger.show', [$group, $line->journalEntry]) }}">
                                {{ $line->journalEntry->entry_number }}
                            </a>
                        </td>
                        <td>
                            {{ $line->description ?? $line->journalEntry->description }}
                            @if ($line->journalEntry->isReversed())
                                <span class="badge badge-danger">reversed</span>
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
                    <x-empty colspan="6">No posted lines for this account.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
