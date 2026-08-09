@extends('layouts.app')
@section('title', 'Join '.$group->name)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('home') }}">Your funds</a></p>
            <h1>{{ $group->name }}</h1>
        </div>
    </div>

    <div class="card" style="max-width: 34rem;">
        @if ($group->description)
            <p class="muted">{{ $group->description }}</p>
        @endif

        @if ($membership?->status === \App\Models\GroupMembership::STATUS_REQUESTED)
            <p>Your request is waiting for an administrator.</p>
        @elseif ($membership?->status === \App\Models\GroupMembership::STATUS_REJECTED)
            <p>A previous request was declined. You can ask again.</p>
        @endif

        <form method="POST" action="{{ route('groups.join', $group) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Request membership</button>
        </form>

        <p class="small muted" style="margin: 0.9rem 0 0;">
            Membership gives you access to this fund's financial activity. It gives
            you no access to any other fund.
        </p>
    </div>
@endsection
