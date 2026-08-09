@extends('layouts.app')
@section('title', 'Activity')

@section('content')
    <div class="page-head">
        <div>
            <h1>Activity</h1>
            <p class="muted small">One timeline for this fund. Only verified transactions have moved money.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('g.transactions.create', $group) }}">Record a transaction</a>
    </div>

    <form method="GET" class="filters card">
        <div class="field">
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="">All</option>
                @foreach (['contribution','loan_disbursement','loan_repayment','treasury_transfer','treasury_exchange','fee','adjustment'] as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst(str_replace('_',' ',$type)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach (['pending','verified','rejected'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="member_id">Member</label>
            <select id="member_id" name="member_id">
                <option value="">All</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((int) ($filters['member_id'] ?? 0) === $member->id)>{{ $member->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="treasury_id">Treasury</label>
            <select id="treasury_id" name="treasury_id">
                <option value="">All</option>
                @foreach ($treasuries as $treasury)
                    <option value="{{ $treasury->id }}" @selected((int) ($filters['treasury_id'] ?? 0) === $treasury->id)>{{ $treasury->name }}</option>
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
        <div class="actions">
            <button class="btn">Filter</button>
            <a class="btn" href="{{ route('g.transactions.index', $group) }}">Clear</a>
        </div>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Member</th>
                    <th class="num">Amount</th>
                    <th>Type</th>
                    <th>Treasury</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td class="num">
                            <a href="{{ route('g.transactions.show', [$group, $transaction]) }}">
                                {{ $transaction->occurred_on->format('j M Y') }}
                            </a>
                        </td>
                        <td>{{ $transaction->member?->displayName() ?? '—' }}</td>
                        <td class="num {{ $transaction->direction === 'in' ? 'pos' : ($transaction->direction === 'out' ? 'neg' : '') }}">
                            {{ $transaction->direction === 'in' ? '+' : ($transaction->direction === 'out' ? '−' : '') }}<x-amount :value="$transaction->amount" :currency="$transaction->currency" />
                        </td>
                        <td class="small">
                            {{ $transaction->typeLabel() }}
                            @if ($transaction->loan)
                                <br><a class="small" href="{{ route('g.loans.show', [$group, $transaction->loan]) }}">{{ $transaction->loan->reference }}</a>
                            @endif
                        </td>
                        <td class="small muted">{{ $transaction->treasury?->name }}</td>
                        <td><x-status :value="$transaction->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="6">Nothing matches these filters.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $transactions->links('pagination') }}
    </div>
@endsection
