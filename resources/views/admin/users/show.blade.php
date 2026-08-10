@extends('layouts.app')
@section('title', $user->name)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('admin.users.index') }}">{{ __('admin.users.show_breadcrumb') }}</a></p>
            <h1>{{ $user->name }}</h1>
            <p class="muted small">
                {{ $user->email }} · <x-status :value="$user->status" />
                @if ($user->isSystemAdmin())
                    · <span class="badge badge-info">{{ __('admin.users.system_admin_badge') }}</span>
                @endif
            </p>
        </div>
        <div class="actions">
            @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                <form method="POST" action="{{ route('admin.users.reinstate', $user) }}">
                    @csrf
                    <button class="btn">{{ __('admin.users.reinstate') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                    @csrf
                    <button class="btn btn-danger">{{ __('admin.users.suspend') }}</button>
                </form>
            @endif

            @if ($user->isSystemAdmin())
                <form method="POST" action="{{ route('admin.users.demote', $user) }}">
                    @csrf
                    <button class="btn">{{ __('admin.users.revoke_system_admin') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.promote', $user) }}">
                    @csrf
                    <button class="btn">{{ __('admin.users.make_system_admin') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>{{ __('admin.users.memberships_title') }}</h2>
        <p class="small muted">{{ __('admin.users.memberships_subtitle') }}</p>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('admin.users.memberships_table_fund') }}</th>
                    <th>{{ __('admin.users.memberships_table_role') }}</th>
                    <th>{{ __('admin.users.memberships_table_status') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($memberships as $membership)
                    <tr>
                        <td>{{ $membership->group?->name ?? '—' }}</td>
                        <td>{{ $membership->role }}</td>
                        <td><x-status :value="$membership->status" /></td>
                    </tr>
                @empty
                    <x-empty colspan="3">{{ __('admin.users.memberships_empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
