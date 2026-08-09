@extends('layouts.app')
@section('title', 'Policy v'.$policy->version)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">Policies</a></p>
            <h1>Policy v{{ $policy->version }}</h1>
            <p class="muted small">
                @if ($policy->isActive())
                    <span class="badge badge-ok">Active</span>
                @else
                    <x-status :value="$policy->status" />
                @endif
                @if ($policy->effective_from)
                    · {{ $policy->effective_from->format('j M Y') }}
                    – {{ $policy->effective_until?->format('j M Y') ?? 'present' }}
                @endif
            </p>
        </div>

        @if ($policy->isDraft())
            <div class="actions">
                <a class="btn" href="{{ route('g.policies.edit', [$group, $policy->version]) }}">Edit draft</a>
                <a class="btn btn-primary" href="{{ route('g.policies.publish.confirm', [$group, $policy->version]) }}">Publish</a>
            </div>
        @endif
    </div>

    @if ($policy->isDraft())
        <div class="alert alert-warn">
            This is a draft. It governs nothing and is not authoritative until published.
        </div>
    @endif

    <div class="grid grid-side">
        <div class="grid grid-2">
            @foreach ($config->categories() as $category)
                <div class="card">
                    <h2>{{ $category::label() }}</h2>
                    <dl class="deflist">
                        @foreach ($category::fields() as $field)
                            <dt>{{ $field->label }}</dt>
                            <dd>{{ $field->display($category->get($field->key)) }}</dd>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>

        <div class="stack">
            <div class="card">
                <h3>Provenance</h3>
                <dl class="deflist">
                    <dt>Created by</dt>
                    <dd>{{ $policy->creator?->name }} · {{ $policy->created_at->format('j M Y') }}</dd>
                    @if ($policy->published_at)
                        <dt>Published by</dt>
                        <dd>{{ $policy->publisher?->name }} · {{ $policy->published_at->format('j M Y H:i') }}</dd>
                    @endif
                </dl>
            </div>

            <div class="card">
                <h3>Governs</h3>
                <dl class="deflist">
                    <dt>Loans</dt>
                    <dd>{{ $usage['loans'] }}</dd>
                    <dt>Transactions</dt>
                    <dd>{{ $usage['transactions'] }}</dd>
                </dl>
                <p class="small muted" style="margin-top:0.6rem">
                    These records keep this version permanently, whatever is published later.
                </p>
            </div>

            @if ($policy->isDraft())
                <div class="card">
                    <h3>Discard</h3>
                    <p class="small muted">A draft can be deleted; a published version cannot.</p>
                    <form method="POST" action="{{ route('g.policies.destroy', [$group, $policy->version]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-small">Delete draft</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
