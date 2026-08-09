@extends('layouts.app')
@section('title', 'Users')

@section('content')
    <div class="page-head">
        <div>
            <h1>Users</h1>
            <p class="muted small">Every account registered on the platform, across every fund.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Memberships</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a>
                            <br><span class="small muted">{{ $user->email }}</span>
                        </td>
                        <td><x-status :value="$user->status" /></td>
                        <td>
                            @if ($user->isSystemAdmin())
                                <span class="badge badge-info">System admin</span>
                            @else
                                <span class="muted small">—</span>
                            @endif
                        </td>
                        <td class="num">{{ $user->memberships_count }}</td>
                        <td>
                            <div class="actions">
                                @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                    <form method="POST" action="{{ route('admin.users.reinstate', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">Reinstate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                        @csrf
                                        <button class="btn btn-small btn-danger">Suspend</button>
                                    </form>
                                @endif

                                @if ($user->isSystemAdmin())
                                    <form method="POST" action="{{ route('admin.users.demote', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">Revoke admin</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.promote', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">Make system admin</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="5">No users yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('pagination') }}
    </div>
@endsection
