@extends('layouts.guest')
@section('title', __('auth.login.page_title'))

@section('content')
    <div class="card">
        <h2>{{ __('auth.login.title') }}</h2>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="email">{{ __('auth.login.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label for="password">{{ __('auth.login.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>

            <div class="field check">
                <input id="remember" name="remember" type="checkbox" value="1">
                <label for="remember">{{ __('auth.login.remember') }}</label>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('auth.login.submit') }}</button>
        </form>
    </div>

    <p class="small muted" style="margin-top: 1rem;">
        {{ __('auth.login.no_account') }} <a href="{{ route('register') }}">{{ __('auth.login.register_link') }}</a>.
    </p>
@endsection
