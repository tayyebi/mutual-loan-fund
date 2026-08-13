@extends('layouts.guest')
@section('title', __('auth.register.page_title'))

@section('content')
    <h2>{{ __('auth.register.title') }}</h2>
    <p class="small muted">
        {{ __('auth.register.intro') }}
    </p>

    <p class="small muted">{{ __('wizard.progress_label', ['current' => 1, 'total' => 2]) }}</p>

    <form method="POST" action="{{ route('register.identity') }}">
        @csrf

        <div class="field">
            <label for="name">{{ __('auth.register.name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $name) }}" required autofocus>
        </div>

        <div class="field">
            <label for="email">{{ __('auth.register.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">{{ __('wizard.continue') }}</button>
    </form>

    <p class="small muted" style="margin-top: 1rem;">
        {{ __('auth.register.already_registered') }} <a href="{{ route('login') }}">{{ __('auth.register.login_link') }}</a>.
    </p>
@endsection
