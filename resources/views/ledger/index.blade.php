@extends('layouts.app')
@section('title', __('ledger.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('ledger.index.heading') }}</h1>
            <p class="muted small">
                {{ __('ledger.index.intro') }}
            </p>
        </div>
        @if ($groupContext->isAdmin())
            <a class="btn" href="{{ route('g.ledger.adjustments.create', $group) }}">{{ __('ledger.index.new_adjustment') }}</a>
        @endif
    </div>

    <form method="GET" class="filters card">
        <div class="field">
            <label for="status">{{ __('ledger.index.filter_status_label') }}</label>
            <select id="status" name="status">
                <option value="">{{ __('ledger.index.filter_all') }}</option>
                @foreach (['draft','posted','reversed'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __('ledger.index.status_'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from">{{ __('ledger.index.filter_from_label') }}</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">{{ __('ledger.index.filter_to_label') }}</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
        </div>
        <button class="btn">{{ __('ledger.index.filter_submit') }}</button>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('ledger.index.col_entry') }}</th>
                    <th>{{ __('ledger.index.col_date') }}</th>
                    <th>{{ __('ledger.index.col_description') }}</th>
                    <th>{{ __('ledger.index.col_template') }}</th>
                    <th class="num">{{ __('ledger.index.col_value') }}</th>
                    <th>{{ __('ledger.index.col_status') }}</th>
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
                        <td class="num"><x-datetime :value="$entry->entry_date" /></td>
                        <td>
                            {{ $entry->description }}
                            @if ($entry->transaction)
                                <br><a class="small" href="{{ route('g.transactions.show', [$group, $entry->transaction]) }}">{{ __('ledger.index.transaction_link', ['id' => $entry->transaction_id]) }}</a>
                            @endif
                        </td>
                        <td class="small muted">{{ $entry->template }}</td>
                        <td class="num"><x-amount :value="$total" :currency="$entry->functional_currency" /></td>
                        <td><x-status :value="$entry->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="6">{{ __('ledger.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $entries->links('pagination') }}
    </div>
@endsection
