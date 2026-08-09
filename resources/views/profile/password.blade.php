@extends('layouts.app')
@section('title', 'Change password')

@section('content')
    <div class="page-head">
        <div>
            <h1>Change password</h1>
            <p class="muted small">Use at least 10 characters.</p>
        </div>
        <a class="btn" href="{{ route('p.home') }}">Back to my account</a>
    </div>

    <div class="card" style="max-width: 34rem">
        <form method="POST" action="{{ route('p.password.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                @error('current_password')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password">
                @error('password')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">Change password</button>
        </form>
    </div>
@endsection
