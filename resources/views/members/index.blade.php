@extends('layouts.app')
@section('title', __('members.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('members.index.heading') }}</h1>
            <p class="muted small">{{ __('members.index.intro') }}</p>
        </div>
        {{--
            Guarded by surface rather than by role. A fund administrator reading
            the member list from /u is looking at who else is in the fund; the
            same list on /g is a roster they act on. The question "does this
            experience do that?" is the one that decides.
        --}}
        @surfaces('member.requests')
            <a class="btn" href="@surface('member.requests', $group)">
                {{ __('members.index.requests_link') }} @if ($pending > 0)<span class="badge badge-warn">{{ $pending }}</span>@endif
            </a>
        @endsurfaces
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
                    @surfaces('member.role')<th></th>@endsurfaces
                </tr>
                </thead>
                <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>
                            {{-- GroupPolicy::viewMemberPosition is admin-or-self, so on /u the
                                 roster only links the rows a member may actually open. --}}
                            @if (\App\Domain\Access\SurfaceRoute::serves('member.role') || $groupContext->owns($member))
                                <a href="@surface('member.show', $group, $member)">{{ $member->displayName() }}</a>
                            @else
                                {{ $member->displayName() }}
                            @endif
                            <br><span class="small muted">{{ $member->user?->email }}</span>
                        </td>
                        <td class="mono">{{ $member->costCenter?->code ?? '—' }}</td>
                        <td>{{ $member->role }}</td>
                        <td><x-status :value="$member->status" /></td>
                        <td class="small muted">
                            @if ($member->approved_at)<x-datetime :value="$member->approved_at" />@else —@endif
                        </td>
                        @surfaces('member.role')
                            <td>
                                <div class="actions">
                                    @if ($member->status === \App\Models\GroupMembership::STATUS_SUSPENDED)
                                        <form method="POST" action="@surface('member.reinstate', $group, $member)">
                                            @csrf
                                            <button class="btn btn-small">{{ __('members.index.reinstate') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="@surface('member.suspend', $group, $member)">
                                            @csrf
                                            <button class="btn btn-small btn-danger">{{ __('members.index.suspend') }}</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="@surface('member.role', $group, $member)">
                                        @csrf
                                        <input type="hidden" name="role"
                                               value="{{ $member->role === 'admin' ? 'member' : 'admin' }}">
                                        <button class="btn btn-small">
                                            {{ $member->role === 'admin' ? __('members.index.make_member') : __('members.index.make_admin') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endsurfaces
                    </tr>
                @empty
                    <x-empty colspan="{{ \App\Domain\Access\SurfaceRoute::serves('member.role') ? 6 : 5 }}">{{ __('members.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @surfaces('member.approve')
        <div class="card" style="margin-top: 1rem;">
            <h3>{{ __('members.index.invite_heading') }}</h3>
            <p class="small muted">
                {{ __('members.index.invite_hint') }}
            </p>
            <p class="mono small">{{ route('groups.join', $group) }}</p>
        </div>
    @endsurfaces
@endsection
