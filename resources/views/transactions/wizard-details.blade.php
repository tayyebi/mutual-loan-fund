@extends('layouts.app')
@section('title', __('transactions.wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('transactions.wizard.details_heading')"
        :back-href="route('g.transactions.create', [$group, 'type' => $type])"
        :steps="__('transactions.wizard.steps')"
        :current="2"
    >
        <form method="POST" action="{{ route('g.transactions.create.details', $group) }}">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div class="field">
                <label for="treasury_id">
                    {{ __('transactions.wizard.treasury_label') }}
                    @if (in_array($type, [\App\Models\Transaction::TYPE_TREASURY_TRANSFER, \App\Models\Transaction::TYPE_TREASURY_EXCHANGE], true))
                        <span class="muted">{{ __('transactions.wizard.treasury_source_suffix') }}</span>
                    @endif
                </label>
                <select id="treasury_id" name="treasury_id" required autofocus>
                    @foreach ($treasuries as $treasury)
                        <option value="{{ $treasury->id }}" @selected((int) old('treasury_id', $treasury_id) === $treasury->id)>
                            {{ $treasury->name }} ({{ $treasury->currency }})
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($type === \App\Models\Transaction::TYPE_LOAN_REPAYMENT)
                <div class="field">
                    <label for="loan_id">{{ __('transactions.wizard.loan_label') }}</label>
                    <select id="loan_id" name="loan_id" required>
                        @foreach ($loans as $loan)
                            <option value="{{ $loan->id }}" @selected((int) old('loan_id', $loan_id) === $loan->id)>
                                {{ $loan->reference }} — {{ $loan->principal }} {{ $loan->currency }}
                            </option>
                        @endforeach
                    </select>
                    <span class="hint">{{ __('transactions.wizard.loan_hint') }}</span>
                </div>
            @endif

            @if (in_array($type, [\App\Models\Transaction::TYPE_TREASURY_TRANSFER, \App\Models\Transaction::TYPE_TREASURY_EXCHANGE], true))
                <div class="field">
                    <label for="counter_treasury_id">{{ __('transactions.wizard.destination_treasury_label') }}</label>
                    <select id="counter_treasury_id" name="counter_treasury_id" required>
                        @foreach ($treasuries as $treasury)
                            <option value="{{ $treasury->id }}" @selected((int) old('counter_treasury_id', $counter_treasury_id) === $treasury->id)>
                                {{ $treasury->name }} ({{ $treasury->currency }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
