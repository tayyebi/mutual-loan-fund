@extends('layouts.app')
@section('title', __('system.dashboard.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('system.dashboard.title') }}</h1>
            <p class="muted small">
                {{ __('system.dashboard.subtitle') }}
            </p>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('system.dashboard.users') }}</p>
            <p class="figure">{{ $userCounts['active'] }}</p>
            <p class="small muted" style="margin:0">
                {{ __('system.dashboard.active') }} · {{ $userCounts['suspended'] }} {{ __('system.dashboard.suspended') }} · {{ $userCounts['system_admins'] }} {{ __('system.dashboard.system_admins') }}
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('s.users.index') }}">{{ __('system.dashboard.manage_users') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('system.dashboard.funds') }}</p>
            <p class="figure">{{ $fundCounts['active'] }}</p>
            <p class="small muted" style="margin:0">{{ __('system.dashboard.active') }} · {{ $fundCounts['suspended'] }} {{ __('system.dashboard.suspended') }}</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('s.funds.index') }}">{{ __('system.dashboard.manage_funds') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('system.dashboard.audit') }}</p>
            <p class="small muted" style="margin:0">
                {{ __('system.dashboard.audit_summary') }}
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('s.audit.index') }}">{{ __('system.dashboard.view_audit_log') }}</a>
            </p>
        </div>
    </div>
@endsection
