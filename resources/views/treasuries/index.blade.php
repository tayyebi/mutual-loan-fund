@extends('layouts.app')
@section('title', __('treasuries.index.title'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ __('treasuries.index.heading') }}</h1>
            <p class="muted small">
                {{ __('treasuries.index.intro') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('treasuries.index.col_treasury') }}</th>
                    <th>{{ __('treasuries.index.col_held_at') }}</th>
                    <th class="num">{{ __('treasuries.index.col_ledger_balance') }}</th>
                    <th class="num">{{ __('treasuries.index.col_gold') }}</th>
                    <th>{{ __('treasuries.index.col_last_reconciled') }}</th>
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
                                <span class="muted">{{ __('treasuries.index.no_rate') }}</span>
                            @endif
                        </td>
                        <td class="small">
                            @if ($row['latest_reconciliation'])
                                <x-datetime :value="$row['latest_reconciliation']->as_of" />
                                @if ($row['latest_reconciliation']->isReconciled())
                                    <span class="badge badge-ok">{{ __('treasuries.index.reconciled_badge') }}</span>
                                @else
                                    <span class="badge badge-danger">
                                        {{ __('treasuries.index.differs_by', ['difference' => $row['latest_reconciliation']->difference]) }}
                                    </span>
                                @endif
                            @else
                                <span class="muted">{{ __('treasuries.index.never') }}</span>
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
                                                <label>{{ __('treasuries.index.name_label') }}</label>
                                                <input type="text" name="name" value="{{ $treasury->name }}" required>
                                            </div>
                                            <div class="field">
                                                <label>{{ $treasury->isCrypto() ? __('treasuries.index.address_label') : __('treasuries.index.account_identifier_label') }}</label>
                                                <input type="text" name="external_identifier" value="{{ $treasury->external_identifier }}">
                                            </div>
                                            <div class="field">
                                                <label>{{ __('treasuries.index.status_label') }}</label>
                                                <select name="status">
                                                    <option value="active" @selected($treasury->status === 'active')>{{ __('treasuries.index.status_active') }}</option>
                                                    <option value="inactive" @selected($treasury->status === 'inactive')>{{ __('treasuries.index.status_inactive') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button class="btn btn-small">{{ __('treasuries.index.save_button') }}</button>
                                        <span class="hint">{{ __('treasuries.index.save_hint') }}</span>
                                    </form>

                                    <form method="POST" action="{{ route('g.treasuries.reconcile', [$group, $treasury]) }}">
                                        @csrf
                                        <div class="field-row field-row-3">
                                            <div class="field">
                                                <label>{{ __('treasuries.index.external_balance_label', ['currency' => $treasury->currency]) }}</label>
                                                <input type="text" name="external_balance" inputmode="decimal" required>
                                            </div>
                                            <div class="field">
                                                <label>{{ __('treasuries.index.as_of_label') }}</label>
                                                <input type="date" name="as_of" value="{{ now()->toDateString() }}" required>
                                            </div>
                                            <div class="field">
                                                <label>{{ __('treasuries.index.note_label') }}</label>
                                                <input type="text" name="note">
                                            </div>
                                        </div>
                                        <button class="btn btn-small">{{ __('treasuries.index.reconcile_button') }}</button>
                                        <span class="hint">{{ __('treasuries.index.reconcile_hint') }}</span>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <x-empty colspan="5">{{ __('treasuries.index.empty') }}</x-empty>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($groupContext->isAdmin())
        <div class="card" style="margin-top: 1rem;">
            <h2>{{ __('treasuries.index.add_heading') }}</h2>
            <p class="small muted">{{ __('treasuries.index.add_intro') }}</p>
            <a class="btn btn-primary" href="{{ route('g.treasuries.add', $group) }}">{{ __('treasuries.index.create_button') }}</a>
        </div>
    @endif
@endsection
