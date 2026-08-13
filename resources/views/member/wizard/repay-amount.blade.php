@extends('layouts.app')
@section('title', __('member.repay_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.repay_wizard.amount_heading')"
        :back-href="route('u.borrowing.repay.start', [$group, $loan, 'treasury_id' => $treasury->id])"
        :steps="__('member.repay_wizard.steps')"
        :current="2"
    >
        <p class="muted small">
            {{ __('member.repay_wizard.amount_intro', [
                'reference' => $loan->reference,
                'outstanding' => $outstanding->format(2).' '.$loan->currency,
            ]) }}
        </p>

        <form method="POST" action="{{ route('u.borrowing.repay.amount', [$group, $loan]) }}">
            @csrf
            <input type="hidden" name="treasury_id" value="{{ $treasury->id }}">

            <div class="field">
                <label for="amount">{{ __('member.repay_wizard.amount_label', ['currency' => $loan->currency]) }}</label>
                <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount', $amount) }}" autofocus required>
            </div>

            <div class="actions">
                <button class="btn btn-primary">{{ __('member.wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
