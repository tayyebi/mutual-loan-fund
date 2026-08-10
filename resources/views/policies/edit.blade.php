@extends('layouts.app')
@section('title', __('policies.edit.title', ['version' => $policy->version]))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.policies.index', $group) }}">{{ __('policies.edit.breadcrumb') }}</a></p>
            <h1>{{ __('policies.edit.heading', ['version' => $policy->version]) }}</h1>
            <p class="muted small">
                {{ __('policies.edit.intro') }}
            </p>
        </div>
        <a class="btn btn-primary" href="{{ route('g.policies.publish.confirm', [$group, $policy->version]) }}">{{ __('policies.edit.publish_link') }}</a>
    </div>

    @if ($framework_warnings !== [])
        <div class="alert alert-warn">
            {{ __('policies.edit.framework_drift_heading') }}
            <ul>
                @foreach ($framework_warnings as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('g.policies.update', [$group, $policy->version]) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-2">
            @foreach ($config->categories() as $key => $category)
                <div class="card">
                    <h2>{{ $category::label() }}</h2>

                    @foreach ($category::fields() as $field)
                        @php($name = "config[{$key}][{$field->key}]")
                        @php($id = "{$key}_{$field->key}")
                        @php($value = old("config.{$key}.{$field->key}", $category->get($field->key)))

                        @if ($field->type === 'bool')
                            <div class="field check">
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" @checked($value)>
                                <label for="{{ $id }}">{{ $field->label }}</label>
                            </div>
                        @elseif ($field->type === 'enum')
                            <div class="field">
                                <label for="{{ $id }}">{{ $field->label }}</label>
                                <select id="{{ $id }}" name="{{ $name }}">
                                    @foreach ($field->options as $option => $label)
                                        <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="field">
                                <label for="{{ $id }}">
                                    {{ $field->label }}
                                    @if ($field->suffix)<span class="muted">({{ $field->suffix }})</span>@endif
                                </label>
                                <input id="{{ $id }}" name="{{ $name }}"
                                       type="{{ $field->type === 'integer' ? 'number' : 'text' }}"
                                       @if ($field->type === 'decimal') inputmode="decimal" @endif
                                       value="{{ $value }}">
                            </div>
                        @endif

                        @if ($field->help)
                            <p class="hint" style="margin: -0.4rem 0 0.7rem;">{{ $field->help }}</p>
                        @endif

                        @error("config.{$key}.{$field->key}")
                            <p class="hint error">{{ $message }}</p>
                        @enderror
                        @error("{$key}.{$field->key}")
                            <p class="hint error">{{ $message }}</p>
                        @enderror
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="card" style="margin-top:1rem">
            <div class="actions">
                <button class="btn btn-primary">{{ __('policies.edit.save') }}</button>
                <a class="btn" href="{{ route('g.policies.show', [$group, $policy->version]) }}">{{ __('policies.edit.view_draft') }}</a>
            </div>
            <p class="hint">
                {{ __('policies.edit.monetary_hint') }}
            </p>
        </div>
    </form>
@endsection
