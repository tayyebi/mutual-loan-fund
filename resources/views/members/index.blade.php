@extends('layouts.app')
@section('title', __('members.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('members.index.heading') }}</h1>
            <p class="muted small">{{ __('members.index.intro') }}</p>
        </div>
        @if ($groupContext->isAdmin())
            <a class="btn" href="{{ route('g.members.requests', $group) }}">
                {{ __('members.index.requests_link') }} @if ($pending > 0)<span class="badge badge-warn">{{ $pending }}</span>@endif
            </a>
        @endif
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('members.index.member_header') }}</th>
                    <th>{{ __('members.index.cost_center_header') }}</th>
                    <th>{{ __('members.index.role_header') }}</th>
                    <th>{{ __('members.index.status_header') }}</th>
                    <th>{{ __('members.index.member_since_header') }}</th>
                    @if ($groupContext->isAdmin())<th></th>@endif
                </tr>
                </thead>
                <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>
                            <a href="{{ route('g.members.show', [$group, $member]) }}">{{ $member->displayName() }}</a>
                            <br><span class="small muted">{{ $member->user?->email }}</span>
                        </td>
                        <td class="mono">{{ $member->costCenter?->code ?? '—' }}</td>
                        <td>{{ $member->role }}</td>
                        <td><x-status :value="$member->status" /></td>
                        <td class="small muted">
                            @if ($member->approved_at)<x-datetime :value="$member->approved_at" />@else —@endif
                        </td>
                        @if ($groupContext->isAdmin())
                            <td>
                                <div class="actions">
                                    @if ($member->status === \App\Models\GroupMembership::STATUS_SUSPENDED)
                                        <form method="POST" action="{{ route('g.members.reinstate', [$group, $member]) }}">
                                            @csrf
                                            <button class="btn btn-small">{{ __('members.index.reinstate') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('g.members.suspend', [$group, $member]) }}">
                                            @csrf
                                            <button class="btn btn-small btn-danger">{{ __('members.index.suspend') }}</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('g.members.role', [$group, $member]) }}">
                                        @csrf
                                        <input type="hidden" name="role"
                                               value="{{ $member->role === 'admin' ? 'member' : 'admin' }}">
                                        <button class="btn btn-small">
                                            {{ $member->role === 'admin' ? __('members.index.make_member') : __('members.index.make_admin') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <x-empty colspan="{{ $groupContext->isAdmin() ? 6 : 5 }}">{{ __('members.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($groupContext->isAdmin())
        <div class="card" style="margin-top: 1rem;">
            <h3>{{ __('members.index.invite_heading') }}</h3>
            <p class="small muted">
                {{ __('members.index.invite_hint') }}
            </p>
            <p class="mono small">{{ route('groups.join', $group) }}</p>
        </div>
    @endif
@endsection
