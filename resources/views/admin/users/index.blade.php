@extends('layouts.app')
@section('title', __('admin.users.index_title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('admin.users.index_title') }}</h1>
            <p class="muted small">{{ __('admin.users.index_subtitle') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('admin.users.table_name') }}</th>
                    <th>{{ __('admin.users.table_status') }}</th>
                    <th>{{ __('admin.users.table_role') }}</th>
                    <th>{{ __('admin.users.table_memberships') }}</th>
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
                                <span class="badge badge-info">{{ __('admin.users.system_admin_badge') }}</span>
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
                                        <button class="btn btn-small">{{ __('admin.users.reinstate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                        @csrf
                                        <button class="btn btn-small btn-danger">{{ __('admin.users.suspend') }}</button>
                                    </form>
                                @endif

                                @if ($user->isSystemAdmin())
                                    <form method="POST" action="{{ route('admin.users.demote', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">{{ __('admin.users.revoke_admin') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.promote', $user) }}">
                                        @csrf
                                        <button class="btn btn-small">{{ __('admin.users.make_admin') }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="5">{{ __('admin.users.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('pagination') }}
    </div>
@endsection
