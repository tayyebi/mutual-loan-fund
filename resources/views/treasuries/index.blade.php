@extends('layouts.app')
@section('title', 'Treasuries')

@section('content')
    <div class="page-head">
        <div>
            <h1>Treasuries</h1>
            <p class="muted small">
                Where the fund's assets are held. Balances are ledger balances —
                there is no stored balance to drift.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Treasury</th>
                    <th>Held at</th>
                    <th class="num">Ledger balance</th>
                    <th class="num">≈ 18K gold</th>
                    <th>Last reconciled</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    @php($treasury = $row['treasury'])
                    <tr>
                        <td>
                            {{ $treasury->name }}
                            <br><span class="small muted">{{ $treasury->account?->code }} · {{ $treasury->currency }}</span>
                        </td>
                        <td class="small">
                            <span class="badge">{{ $treasury->type }}</span>
                            @if ($treasury->isCrypto())
                                <br>{{ $treasury->network }}
                                @if ($treasury->explorerUrl())
                                    <br><a class="mono truncate" href="{{ $treasury->explorerUrl() }}" target="_blank" rel="noreferrer noopener">{{ $treasury->external_identifier }}</a>
                                @endif
                            @elseif ($treasury->external_identifier)
                                <br><span class="mono small">{{ $treasury->external_identifier }}</span>
                            @endif
                        </td>
                        <td class="num"><x-amount :value="$row['balance']" /></td>
                        <td class="num">
                            @if ($row['gold'])
                                {{ $row['gold']->format(4) }} g
                            @else
                                <span class="muted">no rate</span>
                            @endif
                        </td>
                        <td class="small">
                            @if ($row['latest_reconciliation'])
                                {{ $row['latest_reconciliation']->as_of->format('j M Y') }}
                                @if ($row['latest_reconciliation']->isReconciled())
                                    <span class="badge badge-ok">reconciled</span>
                                @else
                                    <span class="badge badge-danger">
                                        differs by {{ $row['latest_reconciliation']->difference }}
                                    </span>
                                @endif
                            @else
                                <span class="muted">never</span>
                            @endif
                        </td>
                    </tr>

                    @if ($groupContext->isAdmin())
                        <tr>
                            <td colspan="5" style="background: var(--surface-2);">
                                <div class="grid grid-2">
                                    <form method="POST" action="{{ route('g.treasuries.update', [$group, $treasury]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="field-row field-row-3">
                                            <div class="field">
                                                <label>Name</label>
                                                <input type="text" name="name" value="{{ $treasury->name }}" required>
                                            </div>
                                            <div class="field">
                                                <label>{{ $treasury->isCrypto() ? 'Address' : 'Account identifier' }}</label>
                                                <input type="text" name="external_identifier" value="{{ $treasury->external_identifier }}">
                                            </div>
                                            <div class="field">
                                                <label>Status</label>
                                                <select name="status">
                                                    <option value="active" @selected($treasury->status === 'active')>active</option>
                                                    <option value="inactive" @selected($treasury->status === 'inactive')>inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button class="btn btn-small">Save treasury</button>
                                        <span class="hint">Currency, type and network are fixed once a treasury exists.</span>
                                    </form>

                                    <form method="POST" action="{{ route('g.treasuries.reconcile', [$group, $treasury]) }}">
                                        @csrf
                                        <div class="field-row field-row-3">
                                            <div class="field">
                                                <label>External balance ({{ $treasury->currency }})</label>
                                                <input type="text" name="external_balance" inputmode="decimal" required>
                                            </div>
                                            <div class="field">
                                                <label>As of</label>
                                                <input type="date" name="as_of" value="{{ now()->toDateString() }}" required>
                                            </div>
                                            <div class="field">
                                                <label>Note</label>
                                                <input type="text" name="note">
                                            </div>
                                        </div>
                                        <button class="btn btn-small">Reconcile</button>
                                        <span class="hint">A difference is recorded, never absorbed into the ledger.</span>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <x-empty colspan="5">No treasuries yet.</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($groupContext->isAdmin())
        <div class="card" style="margin-top: 1rem;">
            <h2>Add a treasury</h2>
            <form method="POST" action="{{ route('g.treasuries.store', $group) }}">
                @csrf
                <div class="field-row field-row-3">
                    <div class="field">
                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div class="field">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="crypto" @selected(old('type') === 'crypto')>crypto</option>
                            <option value="bank" @selected(old('type') === 'bank')>bank</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency" required>
                            @foreach ($currencies as $code => $meta)
                                @continue($code === config('fund.gold_unit'))
                                <option value="{{ $code }}" @selected(old('currency', auth()->user()->preferred_currency) === $code)>{{ $code }} — {{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field-row field-row-2">
                    <div class="field">
                        <label for="network">Network <span class="muted">(crypto only)</span></label>
                        <select id="network" name="network">
                            <option value="">—</option>
                            @foreach ($networks as $key => $network)
                                <option value="{{ $key }}" @selected(old('network') === $key)>{{ $network['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="external_identifier">Public address or account identifier</label>
                        <input id="external_identifier" name="external_identifier" type="text" value="{{ old('external_identifier') }}">
                    </div>
                </div>

                <button class="btn btn-primary">Create treasury</button>
                <span class="hint">A ledger account is created with it.</span>
            </form>
        </div>
    @endif
@endsection
