@extends('layouts.app')
@section('title', __('profile.transactions.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('profile.transactions.heading') }}</h1>
            <p class="muted small">{{ __('profile.transactions.intro') }}</p>
        </div>
        <a class="btn" href="{{ route('p.home') }}">{{ __('profile.transactions.back_link') }}</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('profile.transactions.fund_header') }}</th>
                    <th>{{ __('profile.transactions.date_header') }}</th>
                    <th class="num">{{ __('profile.transactions.amount_header') }}</th>
                    <th>{{ __('profile.transactions.type_header') }}</th>
                    <th>{{ __('profile.transactions.status_header') }}</th>
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
                                <x-datetime :value="$transaction->occurred_on" />
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
                    <x-empty colspan="5">{{ __('profile.transactions.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $transactions->links('pagination') }}
    </div>
@endsection
