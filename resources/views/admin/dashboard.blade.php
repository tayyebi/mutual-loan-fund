@extends('layouts.app')
@section('title', 'Platform administration')

@section('content')
    <div class="page-head">
        <div>
            <h1>Platform administration</h1>
            <p class="muted small">
                User accounts and funds across the whole application. Nothing here reaches
                into any fund's members, transactions, loans or ledger.
            </p>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">Users</p>
            <p class="figure">{{ $userCounts['active'] }}</p>
            <p class="small muted" style="margin:0">
                active · {{ $userCounts['suspended'] }} suspended · {{ $userCounts['system_admins'] }} system admin(s)
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('admin.users.index') }}">Manage users</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">Funds</p>
            <p class="figure">{{ $fundCounts['active'] }}</p>
            <p class="small muted" style="margin:0">active · {{ $fundCounts['suspended'] }} suspended</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('admin.funds.index') }}">Manage funds</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">Audit</p>
            <p class="small muted" style="margin:0">
                Every suspension, reinstatement and admin grant taken from this area.
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('admin.audit.index') }}">View audit log</a>
            </p>
        </div>
    </div>
@endsection
