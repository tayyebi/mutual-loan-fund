@extends('layouts.app')
@section('title', __('member.repay_wizard.title'))

@section('content')
    <x-wizard-step
        :title="__('member.repay_wizard.treasury_heading')"
        :back-href="route('u.borrowing.loan', [$group, $loan])"
        :back-label="__('member.wizard.cancel')"
        :steps="__('member.repay_wizard.steps')"
        :current="1"
    >
        <p class="muted small">{{ __('member.repay_wizard.treasury_intro') }}</p>

        <form method="POST" action="{{ route('u.borrowing.repay.start', [$group, $loan]) }}">
            @csrf
            <div class="field">
                <label for="treasury_id">{{ __('member.repay_wizard.treasury_label') }}</label>
                <select id="treasury_id" name="treasury_id" required>
                    @foreach ($treasuries as $treasury)
                        <option value="{{ $treasury->id }}">{{ $treasury->name }} ({{ $treasury->currency }})</option>
                    @endforeach
                </select>
            </div>

            <div class="actions">
                <button class="btn btn-primary">{{ __('member.wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
