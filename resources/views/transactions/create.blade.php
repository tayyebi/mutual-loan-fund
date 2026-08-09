@extends('layouts.app')
@section('title', 'Record a transaction')

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.transactions.index', $group) }}">Activity</a></p>
            <h1>Record a transaction</h1>
            <p class="muted small">
                Submitting records a claim. It becomes financially effective only when
                an administrator verifies it and it is posted to the ledger.
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <form method="POST" action="{{ route('g.transactions.store', $group) }}" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="contribution" @selected(old('type', $type) === 'contribution')>Contribution — money in</option>
                        <option value="loan_repayment" @selected(old('type', $type) === 'loan_repayment')>Loan repayment</option>
                        @if ($isAdmin)
                            <option value="treasury_transfer" @selected(old('type', $type) === 'treasury_transfer')>Treasury transfer</option>
                            <option value="treasury_exchange" @selected(old('type', $type) === 'treasury_exchange')>Treasury exchange</option>
                            <option value="fee" @selected(old('type', $type) === 'fee')>Fee paid from a treasury</option>
                        @endif
                    </select>
                    <span class="hint">Transfers, exchanges and fees are administrative.</span>
                </div>

                <div class="field-row field-row-2">
                    <div class="field">
                        <label for="treasury_id">Treasury {{ $isAdmin ? '(source)' : '' }}</label>
                        <select id="treasury_id" name="treasury_id" required>
                            @foreach ($treasuries as $treasury)
                                <option value="{{ $treasury->id }}" @selected((int) old('treasury_id') === $treasury->id)>
                                    {{ $treasury->name }} ({{ $treasury->currency }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="amount">Amount</label>
                        <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount') }}" required>
                        <span class="hint">In the treasury's currency.</span>
                    </div>
                </div>

                <fieldset>
                    <legend>Loan repayment</legend>
                    <div class="field">
                        <label for="loan_id">Loan</label>
                        <select id="loan_id" name="loan_id">
                            <option value="">—</option>
                            @foreach ($loans as $loan)
                                <option value="{{ $loan->id }}" @selected((int) old('loan_id') === $loan->id)>
                                    {{ $loan->reference }} — {{ $loan->principal }} {{ $loan->currency }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">The split between principal and interest is taken from the loan's schedule.</span>
                    </div>
                </fieldset>

                @if ($isAdmin)
                    <fieldset>
                        <legend>Transfer or exchange</legend>
                        <div class="field-row field-row-3">
                            <div class="field">
                                <label for="counter_treasury_id">Destination treasury</label>
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
                                <label for="counter_amount">Amount received</label>
                                <input id="counter_amount" name="counter_amount" type="text" inputmode="decimal" value="{{ old('counter_amount') }}">
                                <span class="hint">Exchanges only.</span>
                            </div>
                            <div class="field">
                                <label for="fee_amount">Fee</label>
                                <input id="fee_amount" name="fee_amount" type="text" inputmode="decimal" value="{{ old('fee_amount') }}">
                                <span class="hint">Charged to the source treasury.</span>
                            </div>
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend>Evidence</legend>
                    <div class="field-row field-row-2">
                        <div class="field">
                            <label for="occurred_on">Date</label>
                            <input id="occurred_on" name="occurred_on" type="date" value="{{ old('occurred_on', now()->toDateString()) }}" required>
                        </div>
                        <div class="field">
                            <label for="reference">Reference</label>
                            <input id="reference" name="reference" type="text" value="{{ old('reference') }}">
                        </div>
                    </div>

                    <div class="field-row field-row-2">
                        <div class="field">
                            <label for="tx_hash">Blockchain transaction hash</label>
                            <input id="tx_hash" name="tx_hash" type="text" value="{{ old('tx_hash') }}">
                            <span class="hint">The same transfer can never be credited twice.</span>
                        </div>
                        <div class="field">
                            <label for="from_address">Sending address</label>
                            <input id="from_address" name="from_address" type="text" value="{{ old('from_address') }}">
                        </div>
                    </div>

                    <div class="field">
                        <label for="receipt">Receipt</label>
                        <input id="receipt" name="receipt" type="file"
                               accept="{{ collect(config('fund.receipts.mimes'))->map(fn ($m) => '.'.$m)->join(',') }}">
                        <span class="hint">JPEG, PNG or PDF. Stored privately and served only through an authorised route.</span>
                    </div>

                    <div class="field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                    </div>
                </fieldset>

                <button class="btn btn-primary">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>How this becomes real</h3>
            <ol class="small muted" style="padding-left: 1.1rem; margin: 0;">
                <li>You submit the claim.</li>
                <li>The server checks any blockchain evidence.</li>
                <li>An administrator verifies it.</li>
                <li>A balanced journal entry is posted.</li>
                <li>Ledger balances change.</li>
            </ol>
            <p class="small muted" style="margin-top:0.8rem">
                Submitting a hash on its own creates no balance.
            </p>
        </div>
    </div>
@endsection
