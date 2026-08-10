@extends('layouts.app')
@section('title', __('groups.framework.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">{{ __('groups.framework.breadcrumb') }}</a></p>
            <h1>{{ __('groups.framework.heading') }}</h1>
            <p class="muted small">
                {{ __('groups.framework.intro') }}
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <form method="POST" action="{{ route('g.framework.update', $group) }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="financial_framework_id">{{ __('groups.framework.field_label') }}</label>
                    <select id="financial_framework_id" name="financial_framework_id">
                        <option value="" @selected($group->financial_framework_id === null)>{{ __('groups.framework.none') }}</option>
                        @foreach ($frameworks as $framework)
                            <option value="{{ $framework->id }}" @selected($group->financial_framework_id === $framework->id)>
                                {{ $framework->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('financial_framework_id')
                    <p class="hint error">{{ $message }}</p>
                @enderror

                <button class="btn btn-primary">{{ __('groups.framework.submit') }}</button>
            </form>
        </div>

        <div class="card">
            <h3>{{ __('groups.framework.available_heading') }}</h3>
            <dl class="deflist">
                @foreach ($frameworks as $framework)
                    <dt>{{ $framework->name }}</dt>
                    <dd>{{ $framework->description }}</dd>
                @endforeach
            </dl>
        </div>
    </div>
@endsection
