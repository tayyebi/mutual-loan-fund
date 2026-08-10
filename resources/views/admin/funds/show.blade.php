@extends('layouts.app')
@section('title', $fund->name)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('admin.funds.index') }}">{{ __('admin.funds.show_breadcrumb') }}</a></p>
            <h1>{{ $fund->name }}</h1>
            <p class="muted small"><x-status :value="$fund->status" /></p>
        </div>
        <div class="actions">
            @if ($fund->status === \App\Models\Group::STATUS_SUSPENDED)
                <form method="POST" action="{{ route('admin.funds.reinstate', $fund) }}">
                    @csrf
                    <button class="btn">{{ __('admin.funds.reinstate') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.funds.suspend', $fund) }}">
                    @csrf
                    <button class="btn btn-danger">{{ __('admin.funds.suspend') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>{{ __('admin.funds.details_title') }}</h2>
        <p class="small muted">
            {{ __('admin.funds.details_subtitle') }}
        </p>
        <dl class="deflist">
            <dt>{{ __('admin.funds.field_slug') }}</dt>
            <dd class="mono">{{ $fund->slug }}</dd>
            <dt>{{ __('admin.funds.field_description') }}</dt>
            <dd>{{ $fund->description ?? '—' }}</dd>
            <dt>{{ __('admin.funds.field_created_by') }}</dt>
            <dd>{{ $fund->creator?->name ?? '—' }}</dd>
            <dt>{{ __('admin.funds.field_created') }}</dt>
            <dd><x-datetime :value="$fund->created_at" /></dd>
            <dt>{{ __('admin.funds.field_active_members') }}</dt>
            <dd class="num">{{ $fund->active_memberships_count }}</dd>
        </dl>
    </div>
@endsection
