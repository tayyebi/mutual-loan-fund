@extends('layouts.app')
@section('title', __('member.wallet_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.wallet_wizard.address_heading')"
        :back-href="route('u.wallets.register', [$group, 'network' => $network, 'currency' => $currency])"
        :steps="__('member.wallet_wizard.steps')"
        :current="2"
    >
        <p class="muted small">{{ __('member.wallet_wizard.address_intro', ['currency' => $currency]) }}</p>

        <form method="POST" action="{{ route('u.wallets.store', $group) }}">
            @csrf
            <input type="hidden" name="network" value="{{ $network }}">
            <input type="hidden" name="currency" value="{{ $currency }}">

            <div class="field">
                <label for="address">{{ __('member.wallet_wizard.address_label') }}</label>
                <input id="address" name="address" type="text" value="{{ old('address') }}" autofocus required>
                <span class="hint">{{ __('member.wallet_wizard.address_hint') }}</span>
            </div>

            <details class="disclosure" @if (old('label')) open @endif>
                <summary>{{ __('member.wallet_wizard.extras_summary') }}</summary>
                <div class="field">
                    <label for="label">{{ __('member.wallet_wizard.label_label') }}</label>
                    <input id="label" name="label" type="text" value="{{ old('label') }}">
                </div>
            </details>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('member.wallet_wizard.confirm_button') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
