@extends('layouts.app')
@section('title', __('policies.show.title', ['version' => $policy->version]))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">{{ __('policies.show.breadcrumb') }}</a></p>
            <h1>{{ __('policies.show.heading', ['version' => $policy->version]) }}</h1>
            <p class="muted small">
                @if ($policy->isActive())
                    <span class="badge badge-ok">{{ __('policies.show.active_badge') }}</span>
                @else
                    <x-status :value="$policy->status" />
                @endif
                @if ($policy->effective_from)
                    · <x-datetime :value="$policy->effective_from" />
                    – @if ($policy->effective_until)<x-datetime :value="$policy->effective_until" />@else{{ __('policies.show.present') }}@endif
                @endif
            </p>
        </div>

        @if ($policy->isDraft())
            <div class="actions">
                <a class="btn" href="{{ route('g.policies.edit', [$group, $policy->version]) }}">{{ __('policies.show.edit_link') }}</a>
                <a class="btn btn-primary" href="{{ route('g.policies.publish.confirm', [$group, $policy->version]) }}">{{ __('policies.show.publish_link') }}</a>
            </div>
        @endif
    </div>

    @if ($policy->isDraft())
        <div class="alert alert-warn">
            {{ __('policies.show.draft_notice') }}
        </div>
    @endif

    @if ($framework_warnings !== [])
        <div class="alert alert-warn">
            {{ __('policies.show.framework_drift_heading') }}
            <ul>
                @foreach ($framework_warnings as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
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
                <h3>{{ __('policies.show.provenance_heading') }}</h3>
                <dl class="deflist">
                    <dt>{{ __('policies.show.created_by') }}</dt>
                    <dd>{{ $policy->creator?->name }} · <x-datetime :value="$policy->created_at" /></dd>
                    @if ($policy->published_at)
                        <dt>{{ __('policies.show.published_by') }}</dt>
                        <dd>{{ $policy->publisher?->name }} · <x-datetime :value="$policy->published_at" format="j M Y H:i" /></dd>
                    @endif
                </dl>
            </div>

            <div class="card">
                <h3>{{ __('policies.show.governs_heading') }}</h3>
                <dl class="deflist">
                    <dt>{{ __('policies.show.loans') }}</dt>
                    <dd>{{ $usage['loans'] }}</dd>
                    <dt>{{ __('policies.show.transactions') }}</dt>
                    <dd>{{ $usage['transactions'] }}</dd>
                </dl>
                <p class="small muted" style="margin-top:0.6rem">
                    {{ __('policies.show.governs_note') }}
                </p>
            </div>

            @if ($policy->isDraft())
                <div class="card">
                    <h3>{{ __('policies.show.discard_heading') }}</h3>
                    <p class="small muted">{{ __('policies.show.discard_note') }}</p>
                    <form method="POST" action="{{ route('g.policies.destroy', [$group, $policy->version]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-small">{{ __('policies.show.delete_draft') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
