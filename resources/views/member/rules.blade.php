@extends('layouts.app')
@section('title', __('member.rules.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('u.fund', $group) }}">{{ __('member.rules.breadcrumb') }}</a></p>
            <h1>{{ __('member.rules.heading') }}</h1>
            <p class="muted small">{{ __('member.rules.intro') }}</p>
        </div>
    </div>

    @unless ($policy)
        <div class="alert alert-warn">
            {{ __('member.rules.no_active_policy') }}
        </div>
    @else
        @if ($framework_warnings !== [])
            <div class="alert alert-warn">
                {{ __('member.rules.framework_drift_heading') }}
                <ul>
                    @foreach ($framework_warnings as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="muted small">
            {{ __('member.rules.active_since', ['version' => $policy->version]) }}
            @if ($policy->effective_from)<x-datetime :value="$policy->effective_from" />@endif.
        </p>

        {{--
            Accounting and treasury categories are skipped: they configure how
            the books are kept, which is the administrator's instrument, not a
            rule that governs a member.
        --}}
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
