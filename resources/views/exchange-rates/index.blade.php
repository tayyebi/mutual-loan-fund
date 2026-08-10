@extends('layouts.app')
@section('title', __('exchange_rates.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('exchange_rates.index.heading') }}</h1>
            <p class="muted small">
                {{ __('exchange_rates.index.intro', ['unit' => $goldUnit]) }}
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>{{ __('exchange_rates.index.rates_on') }} <x-datetime :value="$date" /></h2>
                    <form method="GET" class="filters">
                        <input type="date" name="date" value="{{ $date->toDateString() }}">
                        <button class="btn btn-small">{{ __('exchange_rates.index.show_button') }}</button>
                    </form>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('exchange_rates.index.col_unit') }}</th>
                            <th class="num">{{ __('exchange_rates.index.col_per_gram') }}</th>
                            <th>{{ __('exchange_rates.index.col_effective') }}</th>
                            <th>{{ __('exchange_rates.index.col_source') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($quotes as $unit => $quote)
                            <tr>
                                <td>{{ $unit }}</td>
                                <td class="num">{{ $quote->unitsPerGram18k->format(6) }}</td>
                                <td class="small">
                                    <x-datetime :value="$quote->effectiveDate" />
                                    @if ($quote->isFallback())
                                        <span class="badge badge-warn">{{ __('exchange_rates.index.carried_forward', ['days' => $quote->ageInDays()]) }}</span>
                                    @endif
                                </td>
                                <td class="small muted">
                                    @if ($quote->isFallback())
                                        {{ __('exchange_rates.index.source_not_entered') }}
                                    @else
                                        {{ __('exchange_rates.index.source_entered') }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty colspan="4">
                                {{ __('exchange_rates.index.empty_rates') }}
                            </x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>{{ __('exchange_rates.index.recent_heading') }}</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('exchange_rates.index.col_effective') }}</th>
                            <th>{{ __('exchange_rates.index.col_unit') }}</th>
                            <th class="num">{{ __('exchange_rates.index.col_per_gram_short') }}</th>
                            <th class="num">{{ __('exchange_rates.index.col_troy_oz') }}</th>
                            <th>{{ __('exchange_rates.index.col_entered_by') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($recent as $rate)
                            <tr>
                                <td class="num"><x-datetime :value="$rate->effective_date" /></td>
                                <td>{{ $rate->unit }}</td>
                                <td class="num">{{ \App\Domain\Money\Decimal::of((string) $rate->units_per_gram_18k)->format(6) }}</td>
                                <td class="num">
                                    @if ($rate->source_troy_ounce_24k)
                                        {{ \App\Domain\Money\Decimal::of((string) $rate->source_troy_ounce_24k)->format(2) }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="small muted">{{ $rate->creator?->name }}</td>
                            </tr>
                        @empty
                            <x-empty colspan="5">{{ __('exchange_rates.index.empty_recent') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            @if ($canManage)
                <div class="card">
                    <h2>{{ __('exchange_rates.index.enter_heading') }}</h2>
                    <form method="POST" action="{{ route('exchange-rates.store') }}">
                        @csrf

                        <div class="field">
                            <label for="unit">{{ __('exchange_rates.index.unit_label') }}</label>
                            <select id="unit" name="unit" required>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="units_per_gram_18k">{{ __('exchange_rates.index.units_per_gram_label') }}</label>
                            <input id="units_per_gram_18k" name="units_per_gram_18k" type="text"
                                   inputmode="decimal" value="{{ old('units_per_gram_18k') }}">
                            <span class="hint">{{ __('exchange_rates.index.units_per_gram_hint') }}</span>
                        </div>

                        <div class="field">
                            <label for="troy_ounce_24k">{{ __('exchange_rates.index.troy_ounce_label') }}</label>
                            <input id="troy_ounce_24k" name="troy_ounce_24k" type="text"
                                   inputmode="decimal" value="{{ old('troy_ounce_24k') }}">
                            <span class="hint">{{ __('exchange_rates.index.troy_ounce_hint') }}</span>
                        </div>

                        <div class="field">
                            <label for="effective_date">{{ __('exchange_rates.index.effective_date_label') }}</label>
                            <input id="effective_date" name="effective_date" type="date"
                                   value="{{ old('effective_date', now()->toDateString()) }}" required>
                        </div>

                        <div class="field">
                            <label for="source_note">{{ __('exchange_rates.index.source_note_label') }}</label>
                            <input id="source_note" name="source_note" type="text" value="{{ old('source_note') }}">
                        </div>

                        <button class="btn btn-primary">{{ __('exchange_rates.index.record_button') }}</button>
                    </form>
                </div>
            @endif

            <div class="card">
                <h3>{{ __('exchange_rates.index.conversions_heading') }}</h3>
                <dl class="deflist">
                    <dt>{{ __('exchange_rates.index.purity_label') }}</dt>
                    <dd>{{ __('exchange_rates.index.purity_value') }}</dd>
                    <dt>{{ __('exchange_rates.index.troy_ounce_dt') }}</dt>
                    <dd>{{ __('exchange_rates.index.troy_ounce_value') }}</dd>
                    <dt>{{ __('exchange_rates.index.troy_oz_24k_label') }}</dt>
                    <dd>{{ __('exchange_rates.index.troy_oz_24k_value', ['grams' => $gramsPerTroyOunce->format(7)]) }}</dd>
                </dl>
                <p class="small muted" style="margin-top:0.6rem">
                    {{ __('exchange_rates.index.conversions_note') }}
                </p>
            </div>

            <div class="card">
                <h3>{{ __('exchange_rates.index.missing_heading') }}</h3>
                <p class="small muted" style="margin:0">
                    {{ __('exchange_rates.index.missing_note') }}
                </p>
            </div>
        </div>
    </div>
@endsection
