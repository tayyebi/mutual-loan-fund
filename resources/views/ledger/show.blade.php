@extends('layouts.app')
@section('title', $entry->entry_number)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.ledger.index', $group) }}">{{ __('ledger.show.breadcrumb') }}</a></p>
            <h1>{{ $entry->entry_number }}</h1>
            <p class="muted small">
                <x-status :value="$entry->status" />
                <x-datetime :value="$entry->entry_date" /> · {{ $entry->template }}
                @if ($entry->period) · {{ __('ledger.show.period_prefix') }} {{ $entry->period->label() }} @endif
            </p>
        </div>
    </div>

    @if ($entry->reverses)
        <div class="alert alert-warn">
            {{ __('ledger.show.reverses_notice') }}
            <a href="{{ route('g.ledger.show', [$group, $entry->reverses]) }}">{{ $entry->reverses->entry_number }}</a>.
            @if ($entry->reason) {{ __('ledger.show.reason_prefix') }} {{ $entry->reason }} @endif
        </div>
    @endif

    @if ($entry->isReversed())
        <div class="alert alert-warn">
            {{ __('ledger.show.reversed_notice') }}
        </div>
    @endif

    <div class="grid grid-side">
        <div class="card">
            <h2>{{ $entry->description }}</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('ledger.show.col_account') }}</th>
                        <th>{{ __('ledger.show.col_cost_center') }}</th>
                        <th class="num">{{ __('ledger.show.col_debit') }}</th>
                        <th class="num">{{ __('ledger.show.col_credit') }}</th>
                        <th class="num">{{ __('ledger.show.col_debit_currency', ['currency' => $entry->functional_currency]) }}</th>
                        <th class="num">{{ __('ledger.show.col_credit_currency', ['currency' => $entry->functional_currency]) }}</th>
                        <th class="num">{{ __('ledger.show.col_gold') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php($debits = \App\Domain\Money\Decimal::zero())
                    @php($credits = \App\Domain\Money\Decimal::zero())
                    @foreach ($entry->lines as $line)
                        @php($debits = $debits->plus((string) $line->functional_debit))
                        @php($credits = $credits->plus((string) $line->functional_credit))
                        <tr>
                            <td>
                                <a href="{{ route('g.accounts.show', [$group, $line->account]) }}">{{ $line->account->label() }}</a>
                                @if ($line->description)<br><span class="small muted">{{ $line->description }}</span>@endif
                            </td>
                            <td class="small">
                                @if ($line->costCenter)
                                    <a href="{{ route('g.cost-centers.show', [$group, $line->costCenter]) }}">{{ $line->costCenter->code }}</a>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="num">@if ($line->isDebit())<x-amount :value="$line->debit" :currency="$line->currency" />@endif</td>
                            <td class="num">@if (! $line->isDebit())<x-amount :value="$line->credit" :currency="$line->currency" />@endif</td>
                            <td class="num">@if ($line->isDebit())<x-amount :value="$line->functional_debit" />@endif</td>
                            <td class="num">@if (! $line->isDebit())<x-amount :value="$line->functional_credit" />@endif</td>
                            <td class="num small">
                                @if ($line->gold_value_snapshot !== null)
                                    {{ \App\Domain\Money\Decimal::of((string) $line->gold_value_snapshot)->format(6) }} g
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="4">{{ __('ledger.show.totals') }}</td>
                        <td class="num"><x-amount :value="$debits" /></td>
                        <td class="num"><x-amount :value="$credits" /></td>
                        <td class="num">
                            @if ($debits->equals($credits))
                                <span class="badge badge-ok">{{ __('ledger.show.balanced') }}</span>
                            @else
                                <span class="badge badge-danger">{{ __('ledger.show.unbalanced') }}</span>
                            @endif
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <p class="small muted" style="margin-top:0.8rem">
                {{ __('ledger.show.valuation_note') }}
            </p>
        </div>

        <div class="stack">
            <div class="card">
                <h3>{{ __('ledger.show.provenance_heading') }}</h3>
                <dl class="deflist">
                    <dt>{{ __('ledger.show.created_label') }}</dt>
                    <dd>{{ $entry->creator?->name }} · <x-datetime :value="$entry->created_at" format="j M Y H:i" /></dd>
                    @if ($entry->posted_at)
                        <dt>{{ __('ledger.show.posted_label') }}</dt>
                        <dd>{{ $entry->poster?->name }} · <x-datetime :value="$entry->posted_at" format="j M Y H:i" /></dd>
                    @endif
                    @if ($entry->transaction)
                        <dt>{{ __('ledger.show.transaction_label') }}</dt>
                        <dd><a href="{{ route('g.transactions.show', [$group, $entry->transaction]) }}">#{{ $entry->transaction_id }}</a></dd>
                    @endif
                    @if ($entry->reason)
                        <dt>{{ __('ledger.show.reason_label') }}</dt>
                        <dd>{{ $entry->reason }}</dd>
                    @endif
                </dl>
            </div>

            @if ($groupContext->isAdmin() && $entry->isPosted() && ! $entry->isReversed())
                <div class="card">
                    <h3>{{ __('ledger.show.reverse_heading') }}</h3>
                    <p class="small muted">
                        {{ __('ledger.show.reverse_intro') }}
                    </p>
                    <form method="POST" action="{{ route('g.ledger.reverse', [$group, $entry]) }}">
                        @csrf
                        <div class="field">
                            <label for="reason">{{ __('ledger.show.reason_input_label') }}</label>
                            <textarea id="reason" name="reason" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-small">{{ __('ledger.show.submit_reversal') }}</button>
                    </form>
                </div>
            @endif

            @if ($entry->reversal->isNotEmpty())
                <div class="card">
                    <h3>{{ __('ledger.show.reversed_by_heading') }}</h3>
                    @foreach ($entry->reversal as $reversal)
                        <p class="small">
                            <a href="{{ route('g.ledger.show', [$group, $reversal]) }}">{{ $reversal->entry_number }}</a>
                            · <x-datetime :value="$reversal->entry_date" />
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
