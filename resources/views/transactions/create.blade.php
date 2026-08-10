@extends('layouts.app')
@section('title', __('transactions.create.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="@surface('transaction.index', $group)">{{ __('transactions.create.breadcrumb') }}</a></p>
            <h1>{{ __('transactions.create.heading') }}</h1>
            <p class="muted small">
                {{ __('transactions.create.intro') }}
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <form method="POST" action="@surface('transaction.store', $group)" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label for="type">{{ __('transactions.create.type_label') }}</label>
                    <select id="type" name="type" required>
                        <option value="contribution" @selected(old('type', $type) === 'contribution')>{{ __('transactions.create.type_contribution') }}</option>
                        <option value="loan_repayment" @selected(old('type', $type) === 'loan_repayment')>{{ __('transactions.create.type_loan_repayment') }}</option>
                        @if ($isAdmin)
                            <option value="treasury_transfer" @selected(old('type', $type) === 'treasury_transfer')>{{ __('transactions.create.type_treasury_transfer') }}</option>
                            <option value="treasury_exchange" @selected(old('type', $type) === 'treasury_exchange')>{{ __('transactions.create.type_treasury_exchange') }}</option>
                            <option value="fee" @selected(old('type', $type) === 'fee')>{{ __('transactions.create.type_fee') }}</option>
                        @endif
                    </select>
                    <span class="hint">{{ __('transactions.create.type_hint') }}</span>
                </div>

                <div class="field-row field-row-2">
                    <div class="field">
                        <label for="treasury_id">{{ __('transactions.create.treasury_label') }} {{ $isAdmin ? __('transactions.create.treasury_source_suffix') : '' }}</label>
                        <select id="treasury_id" name="treasury_id" required>
                            @foreach ($treasuries as $treasury)
                                <option value="{{ $treasury->id }}" @selected((int) old('treasury_id') === $treasury->id)>
                                    {{ $treasury->name }} ({{ $treasury->currency }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="amount">{{ __('transactions.create.amount_label') }}</label>
                        <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount') }}" required>
                        <span class="hint">{{ __('transactions.create.amount_hint') }}</span>
                    </div>
                </div>

                <fieldset>
                    <legend>{{ __('transactions.create.loan_repayment_legend') }}</legend>
                    <div class="field">
                        <label for="loan_id">{{ __('transactions.create.loan_label') }}</label>
                        <select id="loan_id" name="loan_id">
                            <option value="">—</option>
                            @foreach ($loans as $loan)
                                <option value="{{ $loan->id }}" @selected((int) old('loan_id') === $loan->id)>
                                    {{ $loan->reference }} — {{ $loan->principal }} {{ $loan->currency }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">{{ __('transactions.create.loan_hint') }}</span>
                    </div>
                </fieldset>

                @if ($isAdmin)
                    <fieldset>
                        <legend>{{ __('transactions.create.transfer_legend') }}</legend>
                        <div class="field-row field-row-3">
                            <div class="field">
                                <label for="counter_treasury_id">{{ __('transactions.create.destination_treasury_label') }}</label>
                                <select id="counter_treasury_id" name="counter_treasury_id">
                                    <option value="">—</option>
                                    @foreach ($treasuries as $treasury)
                                        <option value="{{ $treasury->id }}" @selected((int) old('counter_treasury_id') === $treasury->id)>
                                            {{ $treasury->name }} ({{ $treasury->currency }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="counter_amount">{{ __('transactions.create.counter_amount_label') }}</label>
                                <input id="counter_amount" name="counter_amount" type="text" inputmode="decimal" value="{{ old('counter_amount') }}">
                                <span class="hint">{{ __('transactions.create.counter_amount_hint') }}</span>
                            </div>
                            <div class="field">
                                <label for="fee_amount">{{ __('transactions.create.fee_label') }}</label>
                                <input id="fee_amount" name="fee_amount" type="text" inputmode="decimal" value="{{ old('fee_amount') }}">
                                <span class="hint">{{ __('transactions.create.fee_hint') }}</span>
                            </div>
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend>{{ __('transactions.create.evidence_legend') }}</legend>
                    <div class="field-row field-row-2">
                        <div class="field">
                            <label for="occurred_on">{{ __('transactions.create.date_label') }}</label>
                            <input id="occurred_on" name="occurred_on" type="date" value="{{ old('occurred_on', now()->toDateString()) }}" required>
                        </div>
                        <div class="field">
                            <label for="reference">{{ __('transactions.create.reference_label') }}</label>
                            <input id="reference" name="reference" type="text" value="{{ old('reference') }}">
                        </div>
                    </div>

                    <div class="field-row field-row-2">
                        <div class="field">
                            <label for="tx_hash">{{ __('transactions.create.tx_hash_label') }}</label>
                            <input id="tx_hash" name="tx_hash" type="text" value="{{ old('tx_hash') }}">
                            <span class="hint">{{ __('transactions.create.tx_hash_hint') }}</span>
                        </div>
                        <div class="field">
                            <label for="from_address">{{ __('transactions.create.from_address_label') }}</label>
                            <input id="from_address" name="from_address" type="text" value="{{ old('from_address') }}">
                        </div>
                    </div>

                    <div class="field">
                        <label for="receipt">{{ __('transactions.create.receipt_label') }}</label>
                        <input id="receipt" name="receipt" type="file"
                               accept="{{ collect(config('fund.receipts.mimes'))->map(fn ($m) => '.'.$m)->join(',') }}">
                        <span class="hint">{{ __('transactions.create.receipt_hint') }}</span>
                    </div>

                    <div class="field">
                        <label for="description">{{ __('transactions.create.description_label') }}</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                    </div>
                </fieldset>

                <button class="btn btn-primary">{{ __('transactions.create.submit') }}</button>
            </form>
        </div>

        <div class="card">
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
    </div>
@endsection
