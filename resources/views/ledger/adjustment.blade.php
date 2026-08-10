@extends('layouts.app')
@section('title', __('ledger.adjustment.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.ledger.index', $group) }}">{{ __('ledger.adjustment.breadcrumb') }}</a></p>
            <h1>{{ __('ledger.adjustment.heading') }}</h1>
            <p class="muted small">
                {{ __('ledger.adjustment.intro') }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('g.ledger.adjustments.store', $group) }}">
        @csrf

        <div class="card">
            <div class="field-row field-row-2">
                <div class="field">
                    <label for="entry_date">{{ __('ledger.adjustment.entry_date_label') }}</label>
                    <input id="entry_date" name="entry_date" type="date"
                           value="{{ old('entry_date', now()->toDateString()) }}" required>
                    <span class="hint">{{ __('ledger.adjustment.entry_date_hint') }}</span>
                </div>
                <div class="field">
                    <label for="description">{{ __('ledger.adjustment.description_label') }}</label>
                    <input id="description" name="description" type="text" value="{{ old('description') }}" required>
                </div>
            </div>

            <div class="field">
                <label for="reason">{{ __('ledger.adjustment.reason_label') }}</label>
                <textarea id="reason" name="reason" required>{{ old('reason') }}</textarea>
                <span class="hint">{{ __('ledger.adjustment.reason_hint') }}</span>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('ledger.adjustment.lines_heading') }}</h2>
            <p class="small muted">
                {{ __('ledger.adjustment.lines_intro') }}
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('ledger.adjustment.col_account') }}</th>
                        <th>{{ __('ledger.adjustment.col_cost_center') }}</th>
                        <th>{{ __('ledger.adjustment.col_side') }}</th>
                        <th>{{ __('ledger.adjustment.col_currency') }}</th>
                        <th>{{ __('ledger.adjustment.col_amount') }}</th>
                        <th>{{ __('ledger.adjustment.col_note') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @for ($i = 0; $i < 4; $i++)
                        <tr>
                            <td>
                                <select name="lines[{{ $i }}][account_id]" @if ($i < 2) required @endif>
                                    @if ($i >= 2)<option value="">—</option>@endif
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}"
                                                @selected(old("lines.$i.account_id") == $account->id)>
                                            {{ $account->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="lines[{{ $i }}][cost_center_id]">
                                    <option value="">—</option>
                                    @foreach ($costCenters as $costCenter)
                                        <option value="{{ $costCenter->id }}"
                                                @selected(old("lines.$i.cost_center_id") == $costCenter->id)>
                                            {{ $costCenter->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="lines[{{ $i }}][side]">
                                    <option value="debit" @selected(old("lines.$i.side") === 'debit')>{{ __('ledger.adjustment.side_debit') }}</option>
                                    <option value="credit" @selected(old("lines.$i.side") === 'credit')>{{ __('ledger.adjustment.side_credit') }}</option>
                                </select>
                            </td>
                            <td>
                                <select name="lines[{{ $i }}][currency]">
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency }}" @selected(old("lines.$i.currency") === $currency)>{{ $currency }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" inputmode="decimal" name="lines[{{ $i }}][amount]"
                                       value="{{ old("lines.$i.amount") }}" @if ($i < 2) required @endif>
                            </td>
                            <td><input type="text" name="lines[{{ $i }}][description]" value="{{ old("lines.$i.description") }}"></td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>

            <div class="actions" style="margin-top:1rem">
                <button class="btn btn-primary">{{ __('ledger.adjustment.submit') }}</button>
                <a class="btn" href="{{ route('g.ledger.index', $group) }}">{{ __('ledger.adjustment.cancel') }}</a>
            </div>
        </div>
    </form>
@endsection
