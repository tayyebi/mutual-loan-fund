@extends('layouts.app')
@section('title', __('system.users.index_title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('system.users.index_title') }}</h1>
            <p class="muted small">{{ __('system.users.index_subtitle') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('system.users.table_name') }}</th>
                    <th>{{ __('system.users.table_status') }}</th>
                    <th>{{ __('system.users.table_role') }}</th>
                    <th>{{ __('system.users.table_memberships') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('s.users.show', $user) }}">{{ $user->name }}</a>
                            <br><span class="small muted">{{ $user->email }}</span>
                        </td>
                        <td><x-status :value="$user->status" /></td>
                        <td>
                            @if ($user->isSystemAdmin())
                                <span class="badge badge-info">{{ __('system.users.system_admin_badge') }}</span>
                            @else
                                <span class="muted small">—</span>
                            @endif
                        </td>
                        <td class="num">{{ $user->memberships_count }}</td>
                        <td>
                            <div class="actions">
                                @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                    <form method="POST" action="{{ route('s.users.reinstate', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">{{ __('system.users.reinstate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('s.users.suspend', $user) }}">
                                        @csrf
                                        <button class="btn btn-small btn-danger">{{ __('system.users.suspend') }}</button>
                                    </form>
                                @endif

                                @if ($user->isSystemAdmin())
                                    <form method="POST" action="{{ route('s.users.demote', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">{{ __('system.users.revoke_admin') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('s.users.promote', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">{{ __('system.users.make_admin') }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="5">{{ __('system.users.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('pagination') }}
    </div>
@endsection
