@extends('layouts.app')
@section('title', 'Members')

@section('content')
    <div class="page-head">
        <div>
            <h1>Members</h1>
            <p class="muted small">Every active member has a cost center; their activity is attributed to it.</p>
        </div>
        @if ($groupContext->isAdmin())
            <a class="btn" href="{{ route('g.members.requests', $group) }}">
                Membership requests @if ($pending > 0)<span class="badge badge-warn">{{ $pending }}</span>@endif
            </a>
        @endif
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Member</th>
                    <th>Cost center</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Member since</th>
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
                        <td class="small muted">{{ $member->approved_at?->format('j M Y') ?? '—' }}</td>
                        @if ($groupContext->isAdmin())
                            <td>
                                <div class="actions">
                                    @if ($member->status === \App\Models\GroupMembership::STATUS_SUSPENDED)
                                        <form method="POST" action="{{ route('g.members.reinstate', [$group, $member]) }}">
                                            @csrf
                                            <button class="btn btn-small">Reinstate</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('g.members.suspend', [$group, $member]) }}">
                                            @csrf
                                            <button class="btn btn-small btn-danger">Suspend</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('g.members.role', [$group, $member]) }}">
                                        @csrf
                                        <input type="hidden" name="role"
                                               value="{{ $member->role === 'admin' ? 'member' : 'admin' }}">
                                        <button class="btn btn-small">
                                            {{ $member->role === 'admin' ? 'Make member' : 'Make admin' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <x-empty colspan="{{ $groupContext->isAdmin() ? 6 : 5 }}">No members yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($groupContext->isAdmin())
        <div class="card" style="margin-top: 1rem;">
            <h3>Invite someone</h3>
            <p class="small muted">
                Funds are not discoverable. Send this link to the person you want to join:
            </p>
            <p class="mono small">{{ route('groups.join', $group) }}</p>
        </div>
    @endif
@endsection
