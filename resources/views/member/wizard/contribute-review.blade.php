@extends('layouts.app')
@section('title', __('member.contribute_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.contribute_wizard.review_heading')"
        :back-href="route('u.money.contribute.amount', [$group, 'treasury_id' => $treasury->id, 'amount' => $amount])"
        :steps="__('member.contribute_wizard.steps')"
        :current="3"
    >
        <p class="figure" style="font-size:1.3rem">{{ $amountFormatted }}</p>
        <p>{{ __('member.contribute_wizard.review_summary', ['treasury' => $treasury->name]) }}</p>
        <p class="small muted">{{ __('member.contribute_wizard.review_note') }}</p>

        <form method="POST" action="{{ route('u.money.store', $group) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="contribution">
            <input type="hidden" name="treasury_id" value="{{ $treasury->id }}">
            <input type="hidden" name="amount" value="{{ $amount }}">

            <details class="disclosure">
                <summary>{{ __('member.contribute_wizard.extras_summary') }}</summary>

                <div class="field">
                    <label for="occurred_on">{{ __('member.contribute_wizard.date_label') }}</label>
                    <input id="occurred_on" name="occurred_on" type="date" value="{{ old('occurred_on', $today) }}" max="{{ $today }}" required>
                </div>

                <div class="field">
                    <label for="description">{{ __('member.contribute_wizard.note_label') }}</label>
                    <textarea id="description" name="description">{{ old('description') }}</textarea>
                </div>

                <div class="field">
                    <label for="reference">{{ __('member.contribute_wizard.reference_label') }}</label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}">
                </div>

                <div class="field">
                    <label for="receipt">{{ __('member.contribute_wizard.receipt_label') }}</label>
                    <input id="receipt" name="receipt" type="file"
                           accept="{{ collect(config('fund.receipts.mimes'))->map(fn ($m) => '.'.$m)->join(',') }}">
                    <span class="hint">{{ __('member.contribute_wizard.receipt_hint') }}</span>
                </div>

                @if ($treasury->network)
                    <div class="field-row field-row-2">
                        <div class="field">
                            <label for="tx_hash">{{ __('member.contribute_wizard.tx_hash_label') }}</label>
                            <input id="tx_hash" name="tx_hash" type="text" value="{{ old('tx_hash') }}">
                        </div>
                        <div class="field">
                            <label for="from_address">{{ __('member.contribute_wizard.from_address_label') }}</label>
                            <input id="from_address" name="from_address" type="text" value="{{ old('from_address') }}">
                        </div>
                    </div>
                    <p class="hint" style="margin-top:-0.3rem">{{ __('member.contribute_wizard.tx_hash_hint') }}</p>
                @endif
            </details>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('member.contribute_wizard.confirm_button') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
