@extends('layouts.guest')
@section('title', __('auth.register.page_title'))

@section('content')
    <h2>{{ __('auth.register.title') }}</h2>
    <p class="small muted">
        {{ __('auth.register.intro') }}
    </p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <label for="name">{{ __('auth.register.name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
        </div>

        <div class="field">
            <label for="email">{{ __('auth.register.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
        </div>

        <div class="field">
            <label for="password">{{ __('auth.register.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
            <span class="hint">{{ __('auth.register.password_hint') }}</span>
        </div>

        <div class="field">
            <label for="password_confirmation">{{ __('auth.register.password_confirmation') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('auth.register.submit') }}</button>
    </form>

    <p class="small muted" style="margin-top: 1rem;">
        {{ __('auth.register.already_registered') }} <a href="{{ route('login') }}">{{ __('auth.register.login_link') }}</a>.
    </p>
@endsection
