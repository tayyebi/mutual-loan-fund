@extends('layouts.app')
@section('title', __('transactions.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('transactions.index.heading') }}</h1>
            <p class="muted small">{{ __('transactions.index.intro') }}</p>
        </div>
        <a class="btn btn-primary" href="@surface('transaction.create', $group)">{{ __('transactions.index.record_button') }}</a>
    </div>

    <form method="GET" class="filters card">
        <div class="field">
            <label for="type">{{ __('transactions.index.filter_type_label') }}</label>
            <select id="type" name="type">
                <option value="">{{ __('transactions.index.filter_all') }}</option>
                @foreach (['contribution','loan_disbursement','loan_repayment','treasury_transfer','treasury_exchange','fee','adjustment'] as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ __('transactions.index.type_'.$type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">{{ __('transactions.index.filter_status_label') }}</label>
            <select id="status" name="status">
                <option value="">{{ __('transactions.index.filter_all') }}</option>
                @foreach (['pending','verified','rejected'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __('transactions.index.status_'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="member_id">{{ __('transactions.index.filter_member_label') }}</label>
            <select id="member_id" name="member_id">
                <option value="">{{ __('transactions.index.filter_all') }}</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((int) ($filters['member_id'] ?? 0) === $member->id)>{{ $member->displayName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="treasury_id">{{ __('transactions.index.filter_treasury_label') }}</label>
            <select id="treasury_id" name="treasury_id">
                <option value="">{{ __('transactions.index.filter_all') }}</option>
                @foreach ($treasuries as $treasury)
                    <option value="{{ $treasury->id }}" @selected((int) ($filters['treasury_id'] ?? 0) === $treasury->id)>{{ $treasury->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from">{{ __('transactions.index.filter_from_label') }}</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">{{ __('transactions.index.filter_to_label') }}</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div class="actions">
            <button class="btn">{{ __('transactions.index.filter_submit') }}</button>
            <a class="btn" href="@surface('transaction.index', $group)">{{ __('transactions.index.filter_clear') }}</a>
        </div>
    </form>

    <div class="card">
    <div class="list-rows">
        @forelse ($transactions as $transaction)
            <x-list-row href="@surface('transaction.show', $group, $transaction)">
                <x-slot:avatar>
                    <x-avatar :name="$transaction->member?->displayName() ?? '·'" size="sm" />
                </x-slot:avatar>

                {{ $transaction->typeLabel() }}
                @if ($transaction->loan)
                    <span class="small muted">· {{ $transaction->loan->reference }}</span>
                @endif

                <x-slot:meta>
                    <x-datetime :value="$transaction->occurred_on" format="j M" />
                    · {{ $transaction->member?->displayName() ?? '—' }}
                    @if ($transaction->treasury) · {{ $transaction->treasury->name }} @endif
                </x-slot:meta>

                <x-slot:trailing>
                    <span class="{{ $transaction->direction === 'in' ? 'pos' : ($transaction->direction === 'out' ? 'neg' : '') }}">
                        {{ $transaction->direction === 'in' ? '+' : ($transaction->direction === 'out' ? '−' : '') }}<x-amount :value="$transaction->amount" :currency="$transaction->currency" />
                    </span>
                    <x-status :value="$transaction->status" />
                </x-slot:trailing>
            </x-list-row>
        @empty
            <x-empty as="list">{{ __('transactions.index.empty') }}</x-empty>
        @endforelse
    </div>
    </div>

    {{ $transactions->links('pagination') }}
@endsection
