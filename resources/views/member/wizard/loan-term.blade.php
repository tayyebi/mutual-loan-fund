@extends('layouts.app')
@section('title', __('member.loan_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.loan_wizard.term_heading')"
        :back-href="route('u.borrowing.request', [$group, 'amount' => $amount, 'currency' => $currency])"
        :steps="__('member.loan_wizard.steps')"
        :current="2"
    >
        <form method="POST" action="{{ route('u.borrowing.request.term', $group) }}">
            @csrf
            <input type="hidden" name="amount" value="{{ $amount }}">
            <input type="hidden" name="currency" value="{{ $currency }}">

            <div class="field">
                <label for="term_months">{{ __('member.loan_wizard.term_label') }}</label>
                <select id="term_months" name="term_months" required autofocus>
                    @foreach ($termOptions as $months)
                        <option value="{{ $months }}" @selected($term === $months)>
                            {{ trans_choice('member.loan_wizard.term_option', $months, ['count' => $months]) }}
                        </option>
                    @endforeach
                </select>
            </div>

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

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
