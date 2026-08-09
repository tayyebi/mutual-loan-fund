@extends('layouts.app')
@section('title', $entry->entry_number)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.ledger.index', $group) }}">Ledger</a></p>
            <h1>{{ $entry->entry_number }}</h1>
            <p class="muted small">
                <x-status :value="$entry->status" />
                {{ $entry->entry_date->format('j M Y') }} · {{ $entry->template }}
                @if ($entry->period) · period {{ $entry->period->label() }} @endif
            </p>
        </div>
    </div>

    @if ($entry->reverses)
        <div class="alert alert-warn">
            This entry reverses
            <a href="{{ route('g.ledger.show', [$group, $entry->reverses]) }}">{{ $entry->reverses->entry_number }}</a>.
            @if ($entry->reason) Reason: {{ $entry->reason }} @endif
        </div>
    @endif

    @if ($entry->isReversed())
        <div class="alert alert-warn">
            This entry has been reversed. It stays in the ledger; the reversing entry
            cancels it.
        </div>
    @endif

    <div class="grid grid-side">
        <div class="card">
            <h2>{{ $entry->description }}</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Account</th>
                        <th>Cost center</th>
                        <th class="num">Debit</th>
                        <th class="num">Credit</th>
                        <th class="num">Debit ({{ $entry->functional_currency }})</th>
                        <th class="num">Credit ({{ $entry->functional_currency }})</th>
                        <th class="num">18K gold</th>
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
                        <td colspan="4">Totals</td>
                        <td class="num"><x-amount :value="$debits" /></td>
                        <td class="num"><x-amount :value="$credits" /></td>
                        <td class="num">
                            @if ($debits->equals($credits))
                                <span class="badge badge-ok">balanced</span>
                            @else
                                <span class="badge badge-danger">unbalanced</span>
                            @endif
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <p class="small muted" style="margin-top:0.8rem">
                Valuation snapshots are frozen at posting time. Later rate changes never
                alter them.
            </p>
        </div>

        <div class="stack">
            <div class="card">
                <h3>Provenance</h3>
                <dl class="deflist">
                    <dt>Created</dt>
                    <dd>{{ $entry->creator?->name }} · {{ $entry->created_at->format('j M Y H:i') }}</dd>
                    @if ($entry->posted_at)
                        <dt>Posted</dt>
                        <dd>{{ $entry->poster?->name }} · {{ $entry->posted_at->format('j M Y H:i') }}</dd>
                    @endif
                    @if ($entry->transaction)
                        <dt>Transaction</dt>
                        <dd><a href="{{ route('g.transactions.show', [$group, $entry->transaction]) }}">#{{ $entry->transaction_id }}</a></dd>
                    @endif
                    @if ($entry->reason)
                        <dt>Reason</dt>
                        <dd>{{ $entry->reason }}</dd>
                    @endif
                </dl>
            </div>

            @if ($groupContext->isAdmin() && $entry->isPosted() && ! $entry->isReversed())
                <div class="card">
                    <h3>Reverse this entry</h3>
                    <p class="small muted">
                        Posted entries are never edited or deleted. A reversal posts the
                        opposite lines, keeping the original visible.
                    </p>
                    <form method="POST" action="{{ route('g.ledger.reverse', [$group, $entry]) }}">
                        @csrf
                        <div class="field">
                            <label for="reason">Reason</label>
                            <textarea id="reason" name="reason" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-small">Post reversal</button>
                    </form>
                </div>
            @endif

            @if ($entry->reversal->isNotEmpty())
                <div class="card">
                    <h3>Reversed by</h3>
                    @foreach ($entry->reversal as $reversal)
                        <p class="small">
                            <a href="{{ route('g.ledger.show', [$group, $reversal]) }}">{{ $reversal->entry_number }}</a>
                            · {{ $reversal->entry_date->format('j M Y') }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
