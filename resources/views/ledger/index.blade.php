@extends('layouts.app')
@section('title', 'Ledger')

@section('content')
    <div class="page-head">
        <div>
            <h1>General ledger</h1>
            <p class="muted small">
                Posted entries are immutable. Corrections are reversals and adjustments,
                so the whole history stays visible.
            </p>
        </div>
        @if ($groupContext->isAdmin())
            <a class="btn" href="{{ route('g.ledger.adjustments.create', $group) }}">New adjustment</a>
        @endif
    </div>

    <form method="GET" class="filters card">
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach (['draft','posted','reversed'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
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
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Entry</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Template</th>
                    <th class="num">Value</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($entries as $entry)
                    @php($total = $entry->lines->reduce(
                        fn ($carry, $line) => $carry->plus((string) $line->functional_debit),
                        \App\Domain\Money\Decimal::zero()
                    ))
                    <tr>
                        <td class="mono">
                            <a href="{{ route('g.ledger.show', [$group, $entry]) }}">{{ $entry->entry_number }}</a>
                        </td>
                        <td class="num">{{ $entry->entry_date->format('j M Y') }}</td>
                        <td>
                            {{ $entry->description }}
                            @if ($entry->transaction)
                                <br><a class="small" href="{{ route('g.transactions.show', [$group, $entry->transaction]) }}">transaction #{{ $entry->transaction_id }}</a>
                            @endif
                        </td>
                        <td class="small muted">{{ $entry->template }}</td>
                        <td class="num"><x-amount :value="$total" :currency="$entry->functional_currency" /></td>
                        <td><x-status :value="$entry->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="6">No journal entries yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $entries->links('pagination') }}
    </div>
@endsection
