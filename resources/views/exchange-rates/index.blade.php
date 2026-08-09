@extends('layouts.app')
@section('title', 'Exchange rates')

@section('content')
    <div class="page-head">
        <div>
            <h1>Exchange rates</h1>
            <p class="muted small">
                Global data, shared by every fund. The reference asset is one gram of 18K
                gold ({{ $goldUnit }}) — not a troy ounce of pure gold.
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <h2>Rates on {{ $date->format('j M Y') }}</h2>
                    <form method="GET" class="filters">
                        <input type="date" name="date" value="{{ $date->toDateString() }}">
                        <button class="btn btn-small">Show</button>
                    </form>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Unit</th>
                            <th class="num">Per gram of 18K</th>
                            <th>Effective</th>
                            <th>Source</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($quotes as $unit => $quote)
                            <tr>
                                <td>{{ $unit }}</td>
                                <td class="num">{{ $quote->unitsPerGram18k->format(6) }}</td>
                                <td class="small">
                                    {{ $quote->effectiveDate->format('j M Y') }}
                                    @if ($quote->isFallback())
                                        <span class="badge badge-warn">carried forward {{ $quote->ageInDays() }} days</span>
                                    @endif
                                </td>
                                <td class="small muted">
                                    @if ($quote->isFallback())
                                        No rate was entered for this date.
                                    @else
                                        Entered for this date.
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty colspan="4">
                                No rates have been entered. Operations still post in their own
                                currency; gold valuation is simply unavailable.
                            </x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>Recently entered</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Effective</th>
                            <th>Unit</th>
                            <th class="num">Per gram 18K</th>
                            <th class="num">Troy oz 24K</th>
                            <th>Entered by</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($recent as $rate)
                            <tr>
                                <td class="num">{{ $rate->effective_date->format('j M Y') }}</td>
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
                            <x-empty colspan="5">Nothing entered yet.</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            @if ($canManage)
                <div class="card">
                    <h2>Enter a rate</h2>
                    <form method="POST" action="{{ route('exchange-rates.store') }}">
                        @csrf

                        <div class="field">
                            <label for="unit">Unit</label>
                            <select id="unit" name="unit" required>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="units_per_gram_18k">1 gram of 18K gold =</label>
                            <input id="units_per_gram_18k" name="units_per_gram_18k" type="text"
                                   inputmode="decimal" value="{{ old('units_per_gram_18k') }}">
                            <span class="hint">Authoritative when entered.</span>
                        </div>

                        <div class="field">
                            <label for="troy_ounce_24k">or 1 troy ounce of 24K gold =</label>
                            <input id="troy_ounce_24k" name="troy_ounce_24k" type="text"
                                   inputmode="decimal" value="{{ old('troy_ounce_24k') }}">
                            <span class="hint">Converted mathematically, not treated as a separate rate.</span>
                        </div>

                        <div class="field">
                            <label for="effective_date">Effective date</label>
                            <input id="effective_date" name="effective_date" type="date"
                                   value="{{ old('effective_date', now()->toDateString()) }}" required>
                        </div>

                        <div class="field">
                            <label for="source_note">Source</label>
                            <input id="source_note" name="source_note" type="text" value="{{ old('source_note') }}">
                        </div>

                        <button class="btn btn-primary">Record rate</button>
                    </form>
                </div>
            @endif

            <div class="card">
                <h3>The conversions</h3>
                <dl class="deflist">
                    <dt>18K purity</dt>
                    <dd>75% pure gold</dd>
                    <dt>Troy ounce</dt>
                    <dd>31.1034768 g</dd>
                    <dt>1 troy oz 24K</dt>
                    <dd>{{ $gramsPerTroyOunce->format(7) }} g of 18K equivalent</dd>
                </dl>
                <p class="small muted" style="margin-top:0.6rem">
                    These are mathematical constants, never entered by hand.
                </p>
            </div>

            <div class="card">
                <h3>Missing rates</h3>
                <p class="small muted" style="margin:0">
                    When no rate exists for a date, the latest earlier one is used and
                    labelled as carried forward. A rate is never invented: a posting that
                    genuinely needs one is refused instead.
                </p>
            </div>
        </div>
    </div>
@endsection
