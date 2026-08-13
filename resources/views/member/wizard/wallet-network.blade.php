@extends('layouts.app')
@section('title', __('member.wallet_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.wallet_wizard.network_heading')"
        :back-href="route('u.wallets.index', $group)"
        :back-label="__('member.wizard.cancel')"
        :steps="__('member.wallet_wizard.steps')"
        :current="1"
    >
        <form method="POST" action="{{ route('u.wallets.register', $group) }}">
            @csrf

            <div class="field">
                <label for="network">{{ __('member.wallet_wizard.network_label') }}</label>
                <select id="network" name="network" required autofocus>
                    @foreach ($networks as $key => $meta)
                        <option value="{{ $key }}" @selected(old('network', $network) === $key)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="currency">{{ __('member.wallet_wizard.currency_label') }}</label>
                <select id="currency" name="currency" required>
                    @foreach ($currencies as $code => $meta)
                        <option value="{{ $code }}" @selected(old('currency', $currency) === $code)>{{ $code }} — {{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="actions">
                <button class="btn btn-primary">{{ __('member.wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
