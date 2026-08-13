@extends('layouts.app')
@section('title', __('transactions.wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('transactions.wizard.amount_heading')"
        :back-href="route('g.transactions.create.details', [
            $group,
            'type' => $type,
            'treasury_id' => $treasury_id,
            'counter_treasury_id' => $counter_treasury_id,
            'loan_id' => $loan_id,
        ])"
        :steps="__('transactions.wizard.steps')"
        :current="3"
    >
        <form method="POST" action="{{ route('g.transactions.create.amount', $group) }}">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="treasury_id" value="{{ $treasury_id }}">
            @if ($counter_treasury_id)
                <input type="hidden" name="counter_treasury_id" value="{{ $counter_treasury_id }}">
            @endif
            @if ($loan_id)
                <input type="hidden" name="loan_id" value="{{ $loan_id }}">
            @endif

            <div class="field">
                <label for="amount">{{ __('transactions.wizard.amount_label', ['currency' => $treasury?->currency]) }}</label>
                <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount', $amount) }}" autofocus required>
            </div>

            @if ($type === \App\Models\Transaction::TYPE_TREASURY_EXCHANGE)
                <div class="field">
                    <label for="counter_amount">{{ __('transactions.wizard.counter_amount_label') }}</label>
                    <input id="counter_amount" name="counter_amount" type="text" inputmode="decimal" value="{{ old('counter_amount', $counter_amount) }}" required>
                </div>
            @endif

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
