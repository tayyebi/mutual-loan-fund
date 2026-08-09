@extends('layouts.guest')
@section('title', 'Register')

@section('content')
    <div class="card">
        <h2>Create an account</h2>
        <p class="small muted">
            An account is a person. Funds are private: you join one through a link
            from its administrator.
        </p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password">
                <span class="hint">At least 10 characters.</span>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>

    <p class="small muted" style="margin-top: 1rem;">
        Already registered? <a href="{{ route('login') }}">Sign in</a>.
    </p>
@endsection
