@extends('layouts.app')
@section('title', __('member.loan_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.loan_wizard.amount_heading')"
        :back-href="route('u.borrowing', $group)"
        :back-label="__('wizard.cancel')"
        :steps="__('member.loan_wizard.steps')"
        :current="1"
    >
        @unless ($loanPolicy->enabled())
            <div class="alert alert-warn">{{ __('member.loan_wizard.not_lending_notice') }}</div>
        @endunless

        <form method="POST" action="{{ route('u.borrowing.request', $group) }}">
            @csrf

            <div class="field">
                <label for="amount">{{ __('member.loan_wizard.amount_label') }}</label>
                <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount', $amount) }}" autofocus required>
            </div>

            @if (count($currencies) > 1)
                <div class="field">
                    <label for="currency">{{ __('member.loan_wizard.currency_label') }}</label>
                    <select id="currency" name="currency" required>
                        @foreach ($currencies as $code)
                            <option value="{{ $code }}" @selected($currency === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="currency" value="{{ $currencies[0] ?? $currency }}">
            @endif

            <p class="small muted">
                {{ __('member.loan_wizard.amount_range_note', [
                    'min' => $loanPolicy->minimumAmount()->format(2),
                    'max' => $loanPolicy->maximumAmount()?->format(2) ?? __('member.loan_wizard.unlimited'),
                ]) }}
            </p>

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
