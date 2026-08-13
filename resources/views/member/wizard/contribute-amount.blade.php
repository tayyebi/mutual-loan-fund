@extends('layouts.app')
@section('title', __('member.contribute_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.contribute_wizard.amount_heading')"
        :back-href="route('u.money.contribute', $group)"
        :steps="__('member.contribute_wizard.steps')"
        :current="2"
    >
        <p class="muted small">
            {{ __('member.contribute_wizard.amount_intro', ['treasury' => $treasury->name]) }}
        </p>

        <form method="POST" action="{{ route('u.money.contribute.amount', $group) }}">
            @csrf
            <input type="hidden" name="treasury_id" value="{{ $treasury->id }}">

            <div class="field">
                <label for="amount">{{ __('member.contribute_wizard.amount_label', ['currency' => $treasury->currency]) }}</label>
                <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount', $amount) }}" autofocus required>
            </div>

            <div class="actions">
                <button class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
