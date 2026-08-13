@extends('layouts.app')
@section('title', __('profile.password.title'))

@section('content')
    <x-wizard-step
        :title="__('profile.password.heading')"
        :back-href="route('p.home')"
        :back-label="__('profile.password.back_link')"
        :steps="__('profile.password.steps')"
        :current="1"
    >
        <p class="muted small">{{ __('profile.password.intro') }}</p>

        <form method="POST" action="{{ route('p.password.verify') }}">
            @csrf

            <div class="field">
                <label for="current_password">{{ __('profile.password.current_label') }}</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" autofocus>
                @error('current_password')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">{{ __('wizard.continue') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
