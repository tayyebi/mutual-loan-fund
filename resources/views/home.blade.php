@extends('layouts.app')
@section('title', __('home.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('home.title') }}</h1>
            <p class="muted small">{{ __('home.subtitle') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('groups.create') }}">{{ __('home.create_fund') }}</a>
    </div>

    <div class="grid grid-2">
        @forelse ($memberships as $membership)
            <div class="card">
                <div class="card-head">
                    <h2 style="margin:0">
                        @if ($membership->hasAccess())
                            <a href="{{ route('u.dashboard', $membership->group) }}">{{ $membership->group->name }}</a>
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
                    {{ __('home.role', ['role' => $membership->role]) }}
                    @if ($membership->costCenter)
                        · {{ __('home.cost_center', ['code' => $membership->costCenter->code]) }}
                    @endif
                </p>

                @unless ($membership->hasAccess())
                    <p class="small muted" style="margin: 0.5rem 0 0">
                        {{ __('home.waiting_approval') }}
                    </p>
                @endunless
            </div>
        @empty
            <div class="card">
                <p>{{ __('home.no_fund') }}</p>
                <p class="small muted">
                    {{ __('home.create_or_join') }}
                </p>
            </div>
        @endforelse
    </div>
@endsection
