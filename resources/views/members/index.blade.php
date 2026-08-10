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
    <div class="list-rows">
        @forelse ($members as $member)
            <div class="list-row-item">
                @php
                    // GroupPolicy::viewMemberPosition is admin-or-self, so on /u the
                    // roster only links the rows a member may actually open.
                    $linkable = \App\Domain\Access\SurfaceRoute::serves('member.role') || $groupContext->owns($member);
                @endphp
                <{{ $linkable ? 'a' : 'div' }}
                    @if ($linkable) href="{{ \App\Domain\Access\SurfaceRoute::to('member.show', $group, $member) }}" @endif
                    class="list-row">
                    <span class="list-row-lead">
                        <x-avatar :name="$member->displayName()" />
                    </span>
                    <span class="list-row-body">
                        <span class="list-row-title">{{ $member->displayName() }}</span>
                        <span class="list-row-meta">
                            {{ $member->role }}
                            · {{ $member->costCenter?->code ?? '—' }}
                            @if ($member->approved_at)
                                · <x-datetime :value="$member->approved_at" />
                            @endif
                        </span>
                    </span>
                    <span class="list-row-trail">
                        <x-status :value="$member->status" />
                    </span>
                </{{ $linkable ? 'a' : 'div' }}>

                @surfaces('member.role')
                    <div class="list-row-actions">
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
                    </div>
                @endsurfaces
            </div>
        @empty
            <x-empty as="list">{{ __('members.index.empty') }}</x-empty>
        @endforelse
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
