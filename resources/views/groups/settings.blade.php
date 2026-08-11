@extends('layouts.app')
@section('title', __('groups.settings.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.dashboard', $group) }}">{{ __('groups.settings.breadcrumb') }}</a></p>
            <h1>{{ __('groups.settings.heading') }}</h1>
            <p class="muted small">
                {{ __('groups.settings.intro') }}
            </p>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('g.settings.update', $group) }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">{{ __('groups.settings.name_label') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name', $group->name) }}" required autofocus>
            </div>
            @error('name')
                <p class="hint error">{{ $message }}</p>
            @enderror

            <div class="field">
                <label for="description">{{ __('groups.settings.description_label') }}</label>
                <textarea id="description" name="description">{{ old('description', $group->description) }}</textarea>
            </div>
            @error('description')
                <p class="hint error">{{ $message }}</p>
            @enderror

            <button class="btn btn-primary">{{ __('groups.settings.submit') }}</button>
        </form>
    </div>
@endsection
