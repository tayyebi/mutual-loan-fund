@extends('layouts.app')
@section('title', __('profile.password.title'))

@section('content')
    <x-wizard-step
        :title="__('profile.password.new_heading')"
        :back-href="route('p.password.edit')"
        :steps="__('profile.password.steps')"
        :current="2"
    >
        {{--
            current_password never touched a URL or the session — verifyPassword()
            rendered this view directly from its own POST, and this hidden field is
            the only place the value travels before the real check in
            ProfileController::updatePassword() (StorePasswordRequest).
        --}}
        <form method="POST" action="{{ route('p.password.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="current_password" value="{{ $currentPassword }}">

            <div class="field">
                <label for="password">{{ __('profile.password.new_label') }}</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" autofocus>
                @error('password')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">{{ __('profile.password.confirm_label') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">{{ __('profile.password.submit') }}</button>
            </div>
        </form>
    </x-wizard-step>
@endsection
