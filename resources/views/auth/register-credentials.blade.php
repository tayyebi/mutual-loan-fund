@extends('layouts.guest')
@section('title', __('auth.register.page_title'))

@section('content')
    <h2>{{ __('auth.register.title') }}</h2>
    <p class="small muted">{{ __('wizard.progress_label', ['current' => 2, 'total' => 2]) }}</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <input type="hidden" name="name" value="{{ $name }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="field">
            <label for="password">{{ __('auth.register.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" autofocus>
            <span class="hint">{{ __('auth.register.password_hint') }}</span>
        </div>

        <div class="field">
            <label for="password_confirmation">{{ __('auth.register.password_confirmation') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('auth.register.submit') }}</button>
    </form>

    <p class="small muted" style="margin-top: 1rem;">
        <a href="{{ route('register', ['name' => $name, 'email' => $email]) }}">{{ __('wizard.back') }}</a>
    </p>
@endsection
