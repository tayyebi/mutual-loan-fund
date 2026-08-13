@extends('layouts.app')
@section('title', __('transactions.wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('transactions.wizard.evidence_heading')"
        :back-href="route('g.transactions.create.amount', [
            $group,
            'type' => $type,
            'treasury_id' => $treasury_id,
            'counter_treasury_id' => $counter_treasury_id,
            'loan_id' => $loan_id,
            'amount' => $amount,
            'counter_amount' => $counter_amount,
        ])"
        :steps="__('transactions.wizard.steps')"
        :current="4"
    >
        <form method="POST" action="{{ route('g.transactions.store', $group) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="treasury_id" value="{{ $treasury_id }}">
            @if ($counter_treasury_id)
                <input type="hidden" name="counter_treasury_id" value="{{ $counter_treasury_id }}">
            @endif
            @if ($loan_id)
                <input type="hidden" name="loan_id" value="{{ $loan_id }}">
            @endif
            <input type="hidden" name="amount" value="{{ $amount }}">
            @if ($type === \App\Models\Transaction::TYPE_TREASURY_EXCHANGE)
                <input type="hidden" name="counter_amount" value="{{ $counter_amount }}">
            @endif

            <div class="field">
                <label for="occurred_on">{{ __('transactions.wizard.date_label') }}</label>
                <input id="occurred_on" name="occurred_on" type="date" value="{{ old('occurred_on', $today) }}" max="{{ $today }}" required>
            </div>

            <details class="disclosure" @if ($errors->any()) open @endif>
                <summary>{{ __('transactions.wizard.extras_summary') }}</summary>

                @if ($type === \App\Models\Transaction::TYPE_TREASURY_TRANSFER)
                    <div class="field">
                        <label for="counter_amount">{{ __('transactions.wizard.counter_amount_label') }}</label>
                        <input id="counter_amount" name="counter_amount" type="text" inputmode="decimal" value="{{ old('counter_amount') }}">
                        <span class="hint">{{ __('transactions.wizard.counter_amount_optional_hint') }}</span>
                    </div>
                @endif

                @if (in_array($type, [\App\Models\Transaction::TYPE_TREASURY_TRANSFER, \App\Models\Transaction::TYPE_TREASURY_EXCHANGE], true))
                    <div class="field">
                        <label for="fee_amount">{{ __('transactions.wizard.fee_amount_label') }}</label>
                        <input id="fee_amount" name="fee_amount" type="text" inputmode="decimal" value="{{ old('fee_amount') }}">
                        <span class="hint">{{ __('transactions.wizard.fee_amount_hint') }}</span>
                    </div>
                @endif

                <div class="field">
                    <label for="reference">{{ __('transactions.wizard.reference_label') }}</label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}">
                </div>

                <div class="field">
                    <label for="description">{{ __('transactions.wizard.description_label') }}</label>
                    <textarea id="description" name="description">{{ old('description') }}</textarea>
                </div>

                <div class="field">
                    <label for="receipt">{{ __('transactions.wizard.receipt_label') }}</label>
                    <input id="receipt" name="receipt" type="file"
                           accept="{{ collect(config('fund.receipts.mimes'))->map(fn ($m) => '.'.$m)->join(',') }}">
                    <span class="hint">{{ __('transactions.wizard.receipt_hint') }}</span>
                </div>

                <div class="field-row field-row-2">
                    <div class="field">
                        <label for="tx_hash">{{ __('transactions.wizard.tx_hash_label') }}</label>
                        <input id="tx_hash" name="tx_hash" type="text" value="{{ old('tx_hash') }}">
                    </div>
                    <div class="field">
                        <label for="from_address">{{ __('transactions.wizard.from_address_label') }}</label>
                        <input id="from_address" name="from_address" type="text" value="{{ old('from_address') }}">
                    </div>
                </div>
                <p class="hint" style="margin-top:-0.3rem">{{ __('transactions.wizard.tx_hash_hint') }}</p>
            </details>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('transactions.wizard.submit_button') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
