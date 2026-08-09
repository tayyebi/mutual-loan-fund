@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
    <div class="card">
        <h2>Sign in</h2>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>

            <div class="field check">
                <input id="remember" name="remember" type="checkbox" value="1">
                <label for="remember">Stay signed in</label>
            </div>

            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
    </div>

    <p class="small muted" style="margin-top: 1rem;">
        No account yet? <a href="{{ route('register') }}">Register</a>.
    </p>
@endsection
