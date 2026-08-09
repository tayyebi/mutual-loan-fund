@extends('layouts.app')
@section('title', 'Fund rules')

@section('content')
    <div class="page-head">
        <div>
            <h1>Fund rules</h1>
            <p class="muted small">
                The rules currently governing new operations. Loans and contributions you
                already have keep the rules they were created under.
            </p>
        </div>
    </div>

    @unless ($policy)
        <div class="alert alert-warn">
            This fund has no active policy. New financial operations are refused until
            an administrator publishes one.
        </div>
    @else
        <p class="muted small">Policy v{{ $policy->version }}, active since {{ $policy->effective_from?->format('j M Y') }}.</p>

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
