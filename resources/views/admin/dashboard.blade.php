@extends('layouts.app')
@section('title', __('admin.dashboard.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('admin.dashboard.title') }}</h1>
            <p class="muted small">
                {{ __('admin.dashboard.subtitle') }}
            </p>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <p class="figure-sub">{{ __('admin.dashboard.users') }}</p>
            <p class="figure">{{ $userCounts['active'] }}</p>
            <p class="small muted" style="margin:0">
                {{ __('admin.dashboard.active') }} · {{ $userCounts['suspended'] }} {{ __('admin.dashboard.suspended') }} · {{ $userCounts['system_admins'] }} {{ __('admin.dashboard.system_admins') }}
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('admin.users.index') }}">{{ __('admin.dashboard.manage_users') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('admin.dashboard.funds') }}</p>
            <p class="figure">{{ $fundCounts['active'] }}</p>
            <p class="small muted" style="margin:0">{{ __('admin.dashboard.active') }} · {{ $fundCounts['suspended'] }} {{ __('admin.dashboard.suspended') }}</p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('admin.funds.index') }}">{{ __('admin.dashboard.manage_funds') }}</a>
            </p>
        </div>

        <div class="card">
            <p class="figure-sub">{{ __('admin.dashboard.audit') }}</p>
            <p class="small muted" style="margin:0">
                {{ __('admin.dashboard.audit_summary') }}
            </p>
            <p class="small muted" style="margin-top:0.6rem">
                <a href="{{ route('admin.audit.index') }}">{{ __('admin.dashboard.view_audit_log') }}</a>
            </p>
        </div>
    </div>
@endsection
