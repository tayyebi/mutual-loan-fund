@extends('layouts.app')
@section('title', 'Your funds')

@section('content')
    <div class="page-head">
        <div>
            <h1>Your funds</h1>
            <p class="muted small">Each fund is independent. Nothing is shared between them.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('groups.create') }}">Create a fund</a>
    </div>

    <div class="grid grid-2">
        @forelse ($memberships as $membership)
            <div class="card">
                <div class="card-head">
                    <h2 style="margin:0">
                        @if ($membership->hasAccess())
                            <a href="{{ route('g.dashboard', $membership->group) }}">{{ $membership->group->name }}</a>
                        @else
                            {{ $membership->group->name }}
                        @endif
                    </h2>
                    <x-status :value="$membership->status" />
                </div>

                @if ($membership->group->description)
                    <p class="small muted">{{ $membership->group->description }}</p>
                @endif

                <p class="small muted" style="margin:0">
                    Role: {{ $membership->role }}
                    @if ($membership->costCenter)
                        · Cost center {{ $membership->costCenter->code }}
                    @endif
                </p>

                @unless ($membership->hasAccess())
                    <p class="small muted" style="margin: 0.5rem 0 0">
                        Waiting for an administrator to approve your request.
                    </p>
                @endunless
            </div>
        @empty
            <div class="card">
                <p>You do not belong to a fund yet.</p>
                <p class="small muted">
                    Create one, or open the join link an administrator sent you.
                </p>
            </div>
        @endforelse
    </div>
@endsection
