@extends('layouts.app')
@section('title', __('profile.preferences.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('profile.preferences.heading') }}</h1>
            <p class="muted small">{{ __('profile.preferences.intro') }}</p>
        </div>
        <a class="btn" href="{{ route('p.home') }}">{{ __('profile.preferences.back_link') }}</a>
    </div>

    <div class="card" style="max-width: 34rem">
        <form method="POST" action="{{ route('p.preferences.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="preferred_locale">{{ __('profile.preferences.language_label') }}</label>
                <select id="preferred_locale" name="preferred_locale">
                    <option value="" @selected(old('preferred_locale', auth()->user()->preferred_locale) === null)>
                        {{ __('profile.preferences.language_browser_default') }}
                    </option>
                    @foreach (config('locales.available') as $code => $locale)
                        <option value="{{ $code }}" @selected(old('preferred_locale', auth()->user()->preferred_locale) === $code)>
                            {{ $locale['label'] }}
                        </option>
                    @endforeach
                </select>
                @error('preferred_locale')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="preferred_currency">{{ __('profile.preferences.currency_label') }} <span class="muted">({{ __('profile.preferences.optional') }})</span></label>
                <select id="preferred_currency" name="preferred_currency">
                    <option value="" @selected(old('preferred_currency', auth()->user()->preferred_currency) === null)>{{ __('profile.preferences.currency_none') }}</option>
                    @foreach (config('fund.currencies') as $code => $currency)
                        <option value="{{ $code }}" @selected(old('preferred_currency', auth()->user()->preferred_currency) === $code)>
                            {{ $currency['label'] }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
                <p class="hint">{{ __('profile.preferences.currency_hint') }}</p>
                @error('preferred_currency')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="timezone">{{ __('profile.preferences.timezone_label') }} <span class="muted">({{ __('profile.preferences.optional') }})</span></label>
                <select id="timezone" name="timezone">
                    <option value="" @selected(old('timezone', auth()->user()->timezone) === null)>{{ __('profile.preferences.timezone_server_default') }}</option>
                    @foreach (\DateTimeZone::listIdentifiers() as $identifier)
                        <option value="{{ $identifier }}" @selected(old('timezone', auth()->user()->timezone) === $identifier)>
                            {{ $identifier }}
                        </option>
                    @endforeach
                </select>
                <p class="hint">{{ __('profile.preferences.timezone_hint') }}</p>
                @error('timezone')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label>{{ __('profile.preferences.weekend_days_label') }} <span class="muted">({{ __('profile.preferences.optional') }})</span></label>
                @php($selectedDays = old('weekend_days', auth()->user()->weekend_days ?? []))
                @php($dayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])
                @foreach ($dayKeys as $day => $key)
                    <div class="field check">
                        <input id="weekend_day_{{ $day }}" name="weekend_days[]" type="checkbox" value="{{ $day }}"
                               @checked(in_array($day, $selectedDays ?? [], false))>
                        <label for="weekend_day_{{ $day }}">{{ __('profile.preferences.days.'.$key) }}</label>
                    </div>
                @endforeach
                <p class="hint">{{ __('profile.preferences.weekend_days_hint') }}</p>
                @error('weekend_days')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">{{ __('profile.preferences.submit') }}</button>
        </form>
    </div>
@endsection
