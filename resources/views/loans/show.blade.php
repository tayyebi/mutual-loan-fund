@extends('layouts.app')
@section('title', 'Loan '.$loan->reference)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.loans.index', $group) }}">Loans</a></p>
            <h1>Loan {{ $loan->reference }}</h1>
            <p class="muted small">
                <x-status :value="$loan->status" />
                {{ $loan->member?->displayName() }}
                · governed by policy
                <a href="{{ route('g.policies.show', [$group, $loan->policyVersion->version]) }}">v{{ $loan->policyVersion->version }}</a>
            </p>
        </div>

        <div class="actions">
            @can('approve', $loan)
                <form method="POST" action="{{ route('g.loans.approve', [$group, $loan]) }}">
                    @csrf
                    <button class="btn btn-primary">Approve</button>
                </form>
            @endcan
            @can('cancel', $loan)
                <form method="POST" action="{{ route('g.loans.cancel', [$group, $loan]) }}">
                    @csrf
                    <button class="btn">Cancel</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <h2>Terms</h2>
                <dl class="deflist">
                    <dt>Principal</dt>
                    <dd><x-amount :value="$loan->principal" :currency="$loan->currency" /></dd>
                    <dt>Interest</dt>
                    <dd>{{ rtrim(rtrim($loan->interest_rate, '0'), '.') ?: '0' }}% ·
                        {{ \App\Domain\Policies\Categories\LoanPolicy::INTEREST_METHODS[$loan->interest_method] ?? $loan->interest_method }}</dd>
                    <dt>Term</dt>
                    <dd>{{ $loan->term_months }} months</dd>
                    <dt>Outstanding principal</dt>
                    <dd><x-amount :value="$outstanding" :currency="$loan->currency" /></dd>
                    <dt>Cost center</dt>
                    <dd>
                        @if ($loan->costCenter)
                            <a href="{{ route('g.cost-centers.show', [$group, $loan->costCenter]) }}">{{ $loan->costCenter->label() }}</a>
                        @else — @endif
                    </dd>
                    @if ($loan->purpose)
                        <dt>Purpose</dt>
                        <dd>{{ $loan->purpose }}</dd>
                    @endif
                    @if ($loan->approved_at)
                        <dt>Decision</dt>
                        <dd>
                            {{ $loan->approver?->name }} · {{ $loan->approved_at->format('j M Y') }}
                            @if ($loan->decision_reason)<br><span class="small muted">{{ $loan->decision_reason }}</span>@endif
                        </dd>
                    @endif
                </dl>
            </div>

            @if ($loan->installments->isNotEmpty())
                <div class="card">
                    <h2>Schedule</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Due</th>
                                <th class="num">Principal</th>
                                <th class="num">Interest</th>
                                <th class="num">Total</th>
                                <th class="num">Paid</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($loan->installments as $installment)
                                <tr>
                                    <td class="num">{{ $installment->sequence }}</td>
                                    <td class="num">{{ $installment->due_date->format('j M Y') }}</td>
                                    <td class="num"><x-amount :value="$installment->principal_amount" /></td>
                                    <td class="num"><x-amount :value="$installment->interest_amount" /></td>
                                    <td class="num"><x-amount :value="$installment->amount" /></td>
                                    <td class="num"><x-amount :value="$installment->paid_amount" /></td>
                                    <td><x-status :value="$installment->status" /></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="card">
                <h2>Transactions</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="num">Amount</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($loan->transactions as $transaction)
                            <tr>
                                <td class="num">
                                    <a href="{{ route('g.transactions.show', [$group, $transaction]) }}">
                                        {{ $transaction->occurred_on->format('j M Y') }}
                                    </a>
                                </td>
                                <td class="small">{{ $transaction->typeLabel() }}</td>
                                <td class="num"><x-amount :value="$transaction->amount" :currency="$transaction->currency" /></td>
                                <td><x-status :value="$transaction->status" /></td>
                            </tr>
                        @empty
                            <x-empty colspan="4">No money has moved on this loan yet.</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            @can('disburse', $loan)
                <div class="card">
                    <h3>Disburse</h3>
                    <form method="POST" action="{{ route('g.loans.disburse', [$group, $loan]) }}">
                        @csrf
                        <div class="field">
                            <label for="treasury_id">From treasury</label>
                            <select id="treasury_id" name="treasury_id" required>
                                @foreach ($treasuries as $treasury)
                                    <option value="{{ $treasury->id }}">{{ $treasury->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="occurred_on">Date paid</label>
                            <input id="occurred_on" name="occurred_on" type="date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="tx_hash">Blockchain hash</label>
                            <input id="tx_hash" name="tx_hash" type="text">
                        </div>
                        <button class="btn btn-primary btn-small">Record disbursement</button>
                        <span class="hint">Creates a transaction to verify; the loan activates when it is posted.</span>
                    </form>
                </div>
            @endcan

            @can('repay', $loan)
                <div class="card">
                    <h3>Record a repayment</h3>
                    <form method="POST" action="{{ route('g.loans.repay', [$group, $loan]) }}">
                        @csrf
                        <div class="field">
                            <label for="repay_treasury">Into treasury</label>
                            <select id="repay_treasury" name="treasury_id" required>
                                @foreach ($treasuries as $treasury)
                                    <option value="{{ $treasury->id }}">{{ $treasury->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="repay_amount">Amount ({{ $loan->currency }})</label>
                            <input id="repay_amount" name="amount" type="text" inputmode="decimal" required>
                        </div>
                        <div class="field">
                            <label for="repay_date">Date</label>
                            <input id="repay_date" name="occurred_on" type="date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="repay_hash">Blockchain hash</label>
                            <input id="repay_hash" name="tx_hash" type="text">
                        </div>
                        <button class="btn btn-primary btn-small">Submit repayment</button>
                        <span class="hint">Interest is taken before principal, from the schedule.</span>
                    </form>
                </div>
            @endcan

            @can('approve', $loan)
                <div class="card">
                    <h3>Reject</h3>
                    <form method="POST" action="{{ route('g.loans.reject', [$group, $loan]) }}">
                        @csrf
                        <div class="field">
                            <label for="reason">Reason</label>
                            <textarea id="reason" name="reason" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-small">Reject loan</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
@endsection
