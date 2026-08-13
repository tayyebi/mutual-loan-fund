@extends('layouts.app')
@section('title', __('treasuries.wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('treasuries.wizard.type_heading')"
        :back-href="route('g.treasuries.index', $group)"
        :back-label="__('wizard.cancel')"
        :steps="__('treasuries.wizard.steps')"
        :current="1"
    >
        <form method="POST" action="{{ route('g.treasuries.add', $group) }}">
            @csrf

            <div class="field">
                <label for="type">{{ __('treasuries.wizard.type_label') }}</label>
                <select id="type" name="type" required>
                    <option value="crypto" @selected(old('type', $type) === 'crypto')>{{ __('treasuries.wizard.type_crypto') }}</option>
                    <option value="bank" @selected(old('type', $type) === 'bank')>{{ __('treasuries.wizard.type_bank') }}</option>
                </select>
            </div>

            <div class="field">
                <label for="currency">{{ __('treasuries.wizard.currency_label') }}</label>
                <select id="currency" name="currency" required>
                    @foreach ($currencies as $code => $meta)
                        @continue($code === config('fund.gold_unit'))
                        <option value="{{ $code }}" @selected(old('currency', $currency) === $code)>{{ $code }} — {{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
