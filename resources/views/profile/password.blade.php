@extends('layouts.app')
@section('title', __('profile.password.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('profile.password.heading') }}</h1>
            <p class="muted small">{{ __('profile.password.intro') }}</p>
        </div>
        <a class="btn" href="{{ route('p.home') }}">{{ __('profile.password.back_link') }}</a>
    </div>

    <div class="card" style="max-width: 34rem">
        <form method="POST" action="{{ route('p.password.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="current_password">{{ __('profile.password.current_label') }}</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                @error('current_password')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">{{ __('profile.password.new_label') }}</label>
                <input id="password" name="password" type="password" required autocomplete="new-password">
                @error('password')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">{{ __('profile.password.confirm_label') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">{{ __('profile.password.submit') }}</button>
        </form>
    </div>
@endsection
