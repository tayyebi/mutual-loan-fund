@extends('layouts.app')
@section('title', __('profile.home.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('profile.home.heading') }}</h1>
            <p class="muted small">{{ __('profile.home.intro') }}</p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>{{ __('profile.home.profile_heading') }}</h2>
            <dl class="deflist">
                <dt>{{ __('profile.home.name_label') }}</dt>
                <dd>{{ $user->name }}</dd>

                <dt>{{ __('profile.home.email_label') }}</dt>
                <dd>{{ $user->email }}</dd>

                <dt>{{ __('profile.home.funds_label') }}</dt>
                <dd>
                    {{ $memberships->count() }}
                    {{ trans_choice('profile.home.fund_word', $memberships->count()) }}
                </dd>
            </dl>
        </div>

        <div class="card">
            <h2>{{ __('profile.home.settings_heading') }}</h2>
            <p>
                <a class="btn" href="{{ route('p.transactions') }}">{{ __('profile.home.transactions_link') }}</a>
            </p>
            <p>
                <a class="btn" href="{{ route('p.preferences.edit') }}">{{ __('profile.home.preferences_link') }}</a>
            </p>
            <p style="margin:0">
                <a class="btn" href="{{ route('p.password.edit') }}">{{ __('profile.home.password_link') }}</a>
            </p>
        </div>
    </div>
@endsection
