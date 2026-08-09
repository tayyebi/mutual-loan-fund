@extends('layouts.app')
@section('title', $fund->name)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('admin.funds.index') }}">Funds</a></p>
            <h1>{{ $fund->name }}</h1>
            <p class="muted small"><x-status :value="$fund->status" /></p>
        </div>
        <div class="actions">
            @if ($fund->status === \App\Models\Group::STATUS_SUSPENDED)
                <form method="POST" action="{{ route('admin.funds.reinstate', $fund) }}">
                    @csrf
                    <button class="btn">Reinstate</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.funds.suspend', $fund) }}">
                    @csrf
                    <button class="btn btn-danger">Suspend</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Details</h2>
        <p class="small muted">
            Only what's needed to operate the platform — the fund's own members,
            transactions, loans and ledger are not shown here.
        </p>
        <dl class="deflist">
            <dt>Slug</dt>
            <dd class="mono">{{ $fund->slug }}</dd>
            <dt>Description</dt>
            <dd>{{ $fund->description ?? '—' }}</dd>
            <dt>Created by</dt>
            <dd>{{ $fund->creator?->name ?? '—' }}</dd>
            <dt>Created</dt>
            <dd>{{ $fund->created_at->format('j M Y') }}</dd>
            <dt>Active members</dt>
            <dd class="num">{{ $fund->active_memberships_count }}</dd>
        </dl>
    </div>
@endsection
