@extends('layouts.app')
@section('title', 'My account')

@section('content')
    <div class="page-head">
        <div>
            <h1>My account</h1>
            <p class="muted small">Your account across every fund.</p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>Profile</h2>
            <dl class="deflist">
                <dt>Name</dt>
                <dd>{{ $user->name }}</dd>

                <dt>Email</dt>
                <dd>{{ $user->email }}</dd>

                <dt>Funds</dt>
                <dd>
                    {{ $memberships->count() }}
                    @if ($memberships->count() === 1)fund @else funds @endif
                </dd>
            </dl>
        </div>

        <div class="card">
            <h2>Settings</h2>
            <p>
                <a class="btn" href="{{ route('p.transactions') }}">My transactions</a>
            </p>
            <p style="margin:0">
                <a class="btn" href="{{ route('p.password.edit') }}">Change password</a>
            </p>
        </div>
    </div>
@endsection
