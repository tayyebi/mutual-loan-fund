@extends('layouts.app')
@section('title', __('policies.member.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('policies.member.heading') }}</h1>
            <p class="muted small">
                {{ __('policies.member.intro') }}
            </p>
        </div>
    </div>

    @unless ($policy)
        <div class="alert alert-warn">
            {{ __('policies.member.no_active_policy') }}
        </div>
    @else
        @if ($framework_warnings !== [])
            <div class="alert alert-warn">
                {{ __('policies.member.framework_drift_heading') }}
                <ul>
                    @foreach ($framework_warnings as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="muted small">
            {{ __('policies.member.active_since', ['version' => $policy->version]) }}
            @if ($policy->effective_from)<x-datetime :value="$policy->effective_from" />@endif.
        </p>

        <div class="grid grid-2">
            @foreach ($config->categories() as $key => $category)
                @continue(in_array($key, ['accounting', 'treasury'], true))
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
    @endunless
@endsection
