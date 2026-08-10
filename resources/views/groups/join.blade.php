@extends('layouts.app')
@section('title', __('groups.join.title', ['name' => $group->name]))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('home') }}">{{ __('groups.join.breadcrumb') }}</a></p>
            <h1>{{ $group->name }}</h1>
        </div>
    </div>

    <div class="card" style="max-width: 34rem;">
        @if ($group->description)
            <p class="muted">{{ $group->description }}</p>
        @endif

        @if ($membership?->status === \App\Models\GroupMembership::STATUS_REQUESTED)
            <p>{{ __('groups.join.pending') }}</p>
        @elseif ($membership?->status === \App\Models\GroupMembership::STATUS_REJECTED)
            <p>{{ __('groups.join.rejected') }}</p>
        @endif

        <form method="POST" action="{{ route('groups.join', $group) }}">
            @csrf
            <button type="submit" class="btn btn-primary">{{ __('groups.join.submit') }}</button>
        </form>

        <p class="small muted" style="margin: 0.9rem 0 0;">
            {{ __('groups.join.hint') }}
        </p>
    </div>
@endsection
