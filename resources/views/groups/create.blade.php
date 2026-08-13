@extends('layouts.app')
@section('title', __('groups.create.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('home') }}">{{ __('groups.create.breadcrumb') }}</a></p>
            <h1>{{ __('groups.create.heading') }}</h1>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            <form method="POST" action="{{ route('groups.store') }}">
                @csrf

                <div class="field">
                    <label for="name">{{ __('groups.create.name_label') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
                </div>

                <details class="disclosure" @if (old('description') || old('financial_framework_id')) open @endif>
                    <summary>{{ __('groups.create.extras_summary') }}</summary>

                    <div class="field">
                        <label for="description">{{ __('groups.create.description_label') }}</label>
                        <textarea id="description" name="description">{{ old('description') }}</textarea>
                    </div>

                    <div class="field">
                        <label for="financial_framework_id">{{ __('groups.create.framework_label') }}</label>
                        <select id="financial_framework_id" name="financial_framework_id">
                            <option value="" @selected(old('financial_framework_id') === null)>{{ __('groups.create.framework_none') }}</option>
                            @foreach ($frameworks as $framework)
                                <option value="{{ $framework->id }}" @selected((string) old('financial_framework_id') === (string) $framework->id)>
                                    {{ $framework->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('financial_framework_id')
                            <p class="hint error">{{ $message }}</p>
                        @enderror
                        <p class="hint">
                            {{ __('groups.create.framework_hint') }}
                        </p>
                    </div>
                </details>

                <button type="submit" class="btn btn-primary" style="margin-top:1rem">{{ __('groups.create.submit') }}</button>
            </form>
        </div>

        <div class="stack">
            <div class="card">
                <h3>{{ __('groups.create.created_with_heading') }}</h3>
                <ul class="small muted" style="padding-left: 1.1rem; margin: 0;">
                    <li>{{ __('groups.create.created_with_chart') }}</li>
                    <li>{{ __('groups.create.created_with_membership') }}</li>
                    <li>{{ __('groups.create.created_with_policy') }}</li>
                </ul>
                <p class="small muted" style="margin-top: 0.8rem;">
                    {{ __('groups.create.transaction_note') }}
                </p>
            </div>

            <div class="card">
                <h3>{{ __('groups.create.frameworks_heading') }}</h3>
                <dl class="deflist">
                    @foreach ($frameworks as $framework)
                        <dt>{{ $framework->name }}</dt>
                        <dd>{{ $framework->description }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
@endsection
