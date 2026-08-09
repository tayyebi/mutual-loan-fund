@extends('layouts.app')
@section('title', 'My transactions')

@section('content')
    <div class="page-head">
        <div>
            <h1>My transactions</h1>
            <p class="muted small">Activity in which you were a member, across all your funds.</p>
        </div>
        <a class="btn" href="{{ route('p.home') }}">Back to my account</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Fund</th>
                    <th>Date</th>
                    <th class="num">Amount</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>
                            <a href="{{ route('g.dashboard', $transaction->group) }}">{{ $transaction->group->name }}</a>
                        </td>
                        <td class="num">
                            <a href="{{ route('g.transactions.show', [$transaction->group, $transaction]) }}">
                                {{ $transaction->occurred_on->format('j M Y') }}
                            </a>
                        </td>
                        <td class="num {{ $transaction->direction === 'in' ? 'pos' : ($transaction->direction === 'out' ? 'neg' : '') }}">
                            {{ $transaction->direction === 'in' ? '+' : ($transaction->direction === 'out' ? '−' : '') }}<x-amount :value="$transaction->amount" :currency="$transaction->currency" />
                        </td>
                        <td class="small">
                            {{ $transaction->typeLabel() }}
                            @if ($transaction->loan)
                                <br><a class="small" href="{{ route('g.loans.show', [$transaction->group, $transaction->loan]) }}">{{ $transaction->loan->reference }}</a>
                            @endif
                        </td>
                        <td><x-status :value="$transaction->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="5">You have no transactions yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $transactions->links('pagination') }}
    </div>
@endsection
