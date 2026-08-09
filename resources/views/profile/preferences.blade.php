@extends('layouts.app')
@section('title', 'Preferences')

@section('content')
    <div class="page-head">
        <div>
            <h1>Preferences</h1>
            <p class="muted small">Personal display settings. None of these change any fund's actual rules or records.</p>
        </div>
        <a class="btn" href="{{ route('p.home') }}">Back to my account</a>
    </div>

    <div class="card" style="max-width: 34rem">
        <form method="POST" action="{{ route('p.preferences.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="preferred_locale">Language</label>
                <select id="preferred_locale" name="preferred_locale">
                    <option value="" @selected(old('preferred_locale', auth()->user()->preferred_locale) === null)>
                        Use my browser's language
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
                <label for="preferred_currency">Default currency <span class="muted">(optional)</span></label>
                <select id="preferred_currency" name="preferred_currency">
                    <option value="" @selected(old('preferred_currency', auth()->user()->preferred_currency) === null)>No preference</option>
                    @foreach (config('fund.currencies') as $code => $currency)
                        <option value="{{ $code }}" @selected(old('preferred_currency', auth()->user()->preferred_currency) === $code)>
                            {{ $currency['label'] }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
                <p class="hint">Pre-selects the currency when you create a new treasury or wallet. Nothing else.</p>
                @error('preferred_currency')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="timezone">Timezone <span class="muted">(optional)</span></label>
                <select id="timezone" name="timezone">
                    <option value="" @selected(old('timezone', auth()->user()->timezone) === null)>Use the server's timezone</option>
                    @foreach (\DateTimeZone::listIdentifiers() as $identifier)
                        <option value="{{ $identifier }}" @selected(old('timezone', auth()->user()->timezone) === $identifier)>
                            {{ $identifier }}
                        </option>
                    @endforeach
                </select>
                <p class="hint">Only changes how dates and times are displayed to you. It never changes due dates or when a period closes.</p>
                @error('timezone')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label>Weekend days <span class="muted">(optional)</span></label>
                @php($selectedDays = old('weekend_days', auth()->user()->weekend_days ?? []))
                @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day => $label)
                    <div class="field check">
                        <input id="weekend_day_{{ $day }}" name="weekend_days[]" type="checkbox" value="{{ $day }}"
                               @checked(in_array($day, $selectedDays ?? [], false))>
                        <label for="weekend_day_{{ $day }}">{{ $label }}</label>
                    </div>
                @endforeach
                <p class="hint">Not used by any feature yet — stored for future scheduling features.</p>
                @error('weekend_days')
                    <p class="hint error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Save preferences</button>
        </form>
    </div>
@endsection
