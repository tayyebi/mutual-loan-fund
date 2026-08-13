@extends('layouts.app')
@section('title', __('member.repay_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.repay_wizard.review_heading')"
        :back-href="route('u.borrowing.repay.amount', [$group, $loan, 'treasury_id' => $treasury->id, 'amount' => $amount])"
        :steps="__('member.repay_wizard.steps')"
        :current="3"
    >
        <p class="figure" style="font-size:1.3rem">{{ $amountFormatted }}</p>
        <p>{{ __('member.repay_wizard.review_summary', ['reference' => $loan->reference, 'treasury' => $treasury->name]) }}</p>
        <p class="small muted">{{ __('member.repay_wizard.review_note') }}</p>

        <form method="POST" action="{{ route('u.borrowing.repay', [$group, $loan]) }}">
            @csrf
            <input type="hidden" name="treasury_id" value="{{ $treasury->id }}">
            <input type="hidden" name="amount" value="{{ $amount }}">

            <details class="disclosure">
                <summary>{{ __('member.repay_wizard.extras_summary') }}</summary>

                <div class="field">
                    <label for="occurred_on">{{ __('member.repay_wizard.date_label') }}</label>
                    <input id="occurred_on" name="occurred_on" type="date" value="{{ old('occurred_on', $today) }}" max="{{ $today }}" required>
                </div>

                <div class="field">
                    <label for="reference">{{ __('member.repay_wizard.reference_label') }}</label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}">
                </div>

                @if ($treasury->network)
                    <div class="field">
                        <label for="tx_hash">{{ __('member.repay_wizard.tx_hash_label') }}</label>
                        <input id="tx_hash" name="tx_hash" type="text" value="{{ old('tx_hash') }}">
                        <span class="hint">{{ __('member.repay_wizard.tx_hash_hint') }}</span>
                    </div>
                @endif
            </details>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('member.repay_wizard.confirm_button') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
