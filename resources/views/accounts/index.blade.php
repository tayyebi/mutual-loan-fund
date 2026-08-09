@extends('layouts.app')
@section('title', 'Chart of accounts')

@section('content')
    <div class="page-head">
        <div>
            <h1>Chart of accounts</h1>
            <p class="muted small">
                A ledger account says what something is. Who it belongs to is a cost
                center; where it is held is a treasury.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Cost center</th>
                    <th class="num">Balance ({{ $currency }})</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($accounts as $account)
                    @php($row = $balances->get($account->getKey()))
                    <tr class="{{ $account->isHeader() ? 'row-header' : '' }}">
                        <td class="mono">{{ $account->code }}</td>
                        <td style="{{ $account->isHeader() ? '' : 'padding-left:1.5rem' }}">
                            <a href="{{ route('g.accounts.show', [$group, $account]) }}">{{ $account->name }}</a>
                            @unless ($account->is_active)<span class="badge">inactive</span>@endunless
                        </td>
                        <td class="small muted">{{ $account->type }}</td>
                        <td class="small muted">{{ $account->requires_cost_center ? 'required' : '—' }}</td>
                        <td class="num">
                            @if ($row)
                                <x-amount :value="$row['balance']" signed />
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:0.8rem">
            Balances are signed by the account's normal side and derived from posted
            lines only.
        </p>
    </div>
@endsection
