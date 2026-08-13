@extends('layouts.app')
@section('title', __('member.loan_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.loan_wizard.review_heading')"
        :back-href="route('u.borrowing.request.term', [$group, 'amount' => $amount, 'currency' => $currency, 'term_months' => $term])"
        :steps="__('member.loan_wizard.steps')"
        :current="3"
    >
        <p class="figure" style="font-size:1.3rem">{{ $amountFormatted }}</p>
        <p>
            {{ trans_choice('member.loan_wizard.review_summary', $term, ['count' => $term]) }}
        </p>
        <p class="small muted">
            {{ __('member.loan_wizard.review_interest_note', [
                'rate' => $loanPolicy->interestRate()->format(2),
            ]) }}
        </p>

        @unless ($eligibility->eligible)
            <div class="alert alert-warn">
                {{ __('member.loan_wizard.cannot_borrow_now') }}
                <ul>
                    @foreach ($eligibility->failures as $failure)
                        <li>{{ $failure }}</li>
                    @endforeach
                </ul>
            </div>
        @endunless

        <form method="POST" action="{{ route('u.borrowing.store', $group) }}">
            @csrf
            <input type="hidden" name="amount" value="{{ $amount }}">
            <input type="hidden" name="currency" value="{{ $currency }}">
            <input type="hidden" name="term_months" value="{{ $term }}">

            <details class="disclosure">
                <summary>{{ __('member.loan_wizard.extras_summary') }}</summary>
                <div class="field">
                    <label for="purpose">{{ __('member.loan_wizard.purpose_label') }}</label>
                    <textarea id="purpose" name="purpose">{{ old('purpose') }}</textarea>
                </div>
            </details>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('member.loan_wizard.confirm_button') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
