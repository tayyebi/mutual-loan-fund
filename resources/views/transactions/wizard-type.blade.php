@extends('layouts.app')
@section('title', __('transactions.wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('transactions.wizard.type_heading')"
        :back-href="route('g.transactions.index', $group)"
        :back-label="__('wizard.cancel')"
        :steps="__('transactions.wizard.steps')"
        :current="1"
    >
        <form method="POST" action="{{ route('g.transactions.create', $group) }}">
            @csrf

            <div class="field">
                <label for="type">{{ __('transactions.wizard.type_label') }}</label>
                <select id="type" name="type" required autofocus>
                    <option value="contribution" @selected(old('type', $type) === 'contribution')>{{ __('transactions.wizard.type_contribution') }}</option>
                    <option value="loan_repayment" @selected(old('type', $type) === 'loan_repayment')>{{ __('transactions.wizard.type_loan_repayment') }}</option>
                    <option value="treasury_transfer" @selected(old('type', $type) === 'treasury_transfer')>{{ __('transactions.wizard.type_treasury_transfer') }}</option>
                    <option value="treasury_exchange" @selected(old('type', $type) === 'treasury_exchange')>{{ __('transactions.wizard.type_treasury_exchange') }}</option>
                    <option value="fee" @selected(old('type', $type) === 'fee')>{{ __('transactions.wizard.type_fee') }}</option>
                </select>
                <span class="hint">{{ __('transactions.wizard.type_hint') }}</span>
            </div>

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>

    <div class="card wizard-card" style="margin-top:1rem">
        <h3>{{ __('transactions.create.sidebar_heading') }}</h3>
        <ol class="small muted" style="padding-left: 1.1rem; margin: 0;">
            <li>{{ __('transactions.create.step_1') }}</li>
            <li>{{ __('transactions.create.step_2') }}</li>
            <li>{{ __('transactions.create.step_3') }}</li>
            <li>{{ __('transactions.create.step_4') }}</li>
            <li>{{ __('transactions.create.step_5') }}</li>
        </ol>
        <p class="small muted" style="margin-top:0.8rem">
            {{ __('transactions.create.sidebar_note') }}
        </p>
    </div>
@endsection
