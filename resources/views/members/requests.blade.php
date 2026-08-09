@extends('layouts.app')
@section('title', 'Membership requests')

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.members.index', $group) }}">Members</a></p>
            <h1>Membership requests</h1>
            <p class="muted small">Approving a member creates their cost center.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Person</th>
                    <th>Requested</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td>
                            {{ $request->displayName() }}
                            <br><span class="small muted">{{ $request->user?->email }}</span>
                        </td>
                        <td class="small muted">{{ $request->requested_at?->format('j M Y') }}</td>
                        <td>
                            <div class="actions">
                                <form method="POST" action="{{ route('g.members.approve', [$group, $request]) }}">
                                    @csrf
                                    <button class="btn btn-small btn-primary">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('g.members.reject', [$group, $request]) }}" class="actions">
                                    @csrf
                                    <input type="text" name="reason" placeholder="Reason (optional)" class="small">
                                    <button class="btn btn-small btn-danger">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-empty colspan="3">No requests are waiting.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
