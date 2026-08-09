@extends('layouts.app')
@section('title', $user->name)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('admin.users.index') }}">Users</a></p>
            <h1>{{ $user->name }}</h1>
            <p class="muted small">
                {{ $user->email }} · <x-status :value="$user->status" />
                @if ($user->isSystemAdmin())
                    · <span class="badge badge-info">System admin</span>
                @endif
            </p>
        </div>
        <div class="actions">
            @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                <form method="POST" action="{{ route('admin.users.reinstate', $user) }}">
                    @csrf
                    <button class="btn">Reinstate</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                    @csrf
                    <button class="btn btn-danger">Suspend</button>
                </form>
            @endif

            @if ($user->isSystemAdmin())
                <form method="POST" action="{{ route('admin.users.demote', $user) }}">
                    @csrf
                    <button class="btn">Revoke system admin</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.promote', $user) }}">
                    @csrf
                    <button class="btn">Make system admin</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Fund memberships</h2>
        <p class="small muted">Which funds this account belongs to, and in what role — no financial detail.</p>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Fund</th>
                    <th>Role</th>
                    <th>Status</th>
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
                    <x-empty colspan="3">Not a member of any fund.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
