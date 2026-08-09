@extends('layouts.app')
@section('title', 'New adjustment')

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.ledger.index', $group) }}">Ledger</a></p>
            <h1>Accounting adjustment</h1>
            <p class="muted small">
                Posted entries are never edited. A correction is its own entry, with a
                reason, and it must balance.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('g.ledger.adjustments.store', $group) }}">
        @csrf

        <div class="card">
            <div class="field-row field-row-2">
                <div class="field">
                    <label for="entry_date">Entry date</label>
                    <input id="entry_date" name="entry_date" type="date"
                           value="{{ old('entry_date', now()->toDateString()) }}" required>
                    <span class="hint">Decides the period. A closed month is refused.</span>
                </div>
                <div class="field">
                    <label for="description">Description</label>
                    <input id="description" name="description" type="text" value="{{ old('description') }}" required>
                </div>
            </div>

            <div class="field">
                <label for="reason">Reason</label>
                <textarea id="reason" name="reason" required>{{ old('reason') }}</textarea>
                <span class="hint">Recorded on the entry and in the audit log.</span>
            </div>
        </div>

        <div class="card">
            <h2>Lines</h2>
            <p class="small muted">
                Total debits must equal total credits. Accounts marked as requiring a cost
                center will refuse a line without one.
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Account</th>
                        <th>Cost center</th>
                        <th>Side</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Note</th>
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
                                    <option value="debit" @selected(old("lines.$i.side") === 'debit')>Debit</option>
                                    <option value="credit" @selected(old("lines.$i.side") === 'credit')>Credit</option>
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
                <button class="btn btn-primary">Post adjustment</button>
                <a class="btn" href="{{ route('g.ledger.index', $group) }}">Cancel</a>
            </div>
        </div>
    </form>
@endsection
