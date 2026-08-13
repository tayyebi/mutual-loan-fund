@extends('layouts.app')
@section('title', __('treasuries.wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('treasuries.wizard.details_heading')"
        :back-href="route('g.treasuries.add', [$group, 'type' => $type, 'currency' => $currency])"
        :steps="__('treasuries.wizard.steps')"
        :current="2"
    >
        <form method="POST" action="{{ route('g.treasuries.store', $group) }}">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="currency" value="{{ $currency }}">

            <div class="field">
                <label for="name">{{ __('treasuries.wizard.name_label') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autofocus required>
            </div>

            <details class="disclosure" @if (old('network') || old('external_identifier')) open @endif>
                <summary>{{ __('treasuries.wizard.extras_summary') }}</summary>

                @if ($type === 'crypto')
                    <div class="field">
                        <label for="network">{{ __('treasuries.wizard.network_label') }}</label>
                        <select id="network" name="network">
                            <option value="">—</option>
                            @foreach ($networks as $key => $network)
                                <option value="{{ $key }}" @selected(old('network') === $key)>{{ $network['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="hint">{{ __('treasuries.wizard.network_crypto_only') }}</span>
                    </div>
                @endif

                <div class="field">
                    <label for="external_identifier">{{ __('treasuries.wizard.external_identifier_label') }}</label>
                    <input id="external_identifier" name="external_identifier" type="text" value="{{ old('external_identifier') }}">
                </div>
            </details>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('treasuries.wizard.create_button') }}</button>
                <span class="hint">{{ __('treasuries.wizard.create_hint') }}</span>
            </div>
        </form>
    </x-wizard-step>
@endsection
