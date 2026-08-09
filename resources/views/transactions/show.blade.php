@extends('layouts.app')
@section('title', 'Transaction #'.$transaction->id)

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.transactions.index', $group) }}">Activity</a></p>
            <h1>{{ $transaction->typeLabel() }} · <x-amount :value="$transaction->amount" :currency="$transaction->currency" /></h1>
            <p class="muted small">
                <x-status :value="$transaction->status" />
                {{ $transaction->occurred_on->format('j M Y') }}
                @if ($transaction->member) · {{ $transaction->member->displayName() }} @endif
                @if ($transaction->policyVersion)
                    · policy <a href="{{ route('g.policies.show', [$group, $transaction->policyVersion->version]) }}">v{{ $transaction->policyVersion->version }}</a>
                @endif
            </p>
        </div>

        @if ($groupContext->isAdmin() && $transaction->isPending())
            <div class="actions">
                <form method="POST" action="{{ route('g.transactions.verify', [$group, $transaction]) }}">
                    @csrf
                    <button class="btn btn-primary">Verify and post</button>
                </form>
                @if ($transaction->hasChainEvidence())
                    <form method="POST" action="{{ route('g.transactions.chain-check', [$group, $transaction]) }}">
                        @csrf
                        <button class="btn">Re-check the chain</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if ($transaction->isPending() && $needsAdminVerification)
        <div class="alert alert-warn">
            This transaction has not moved any money yet. It affects no balance until
            an administrator verifies it.
        </div>
    @endif

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <h2>Details</h2>
                <dl class="deflist">
                    <dt>Treasury</dt>
                    <dd>{{ $transaction->treasury?->name ?? '—' }}</dd>

                    @if ($transaction->counterTreasury)
                        <dt>Destination</dt>
                        <dd>
                            {{ $transaction->counterTreasury->name }}
                            @if ($transaction->counter_amount)
                                — <x-amount :value="$transaction->counter_amount" :currency="$transaction->counter_currency" />
                            @endif
                        </dd>
                    @endif

                    @if ($transaction->fee_amount && (float) $transaction->fee_amount > 0)
                        <dt>Fee</dt>
                        <dd><x-amount :value="$transaction->fee_amount" :currency="$transaction->fee_currency" /></dd>
                    @endif

                    @if ($transaction->loan)
                        <dt>Loan</dt>
                        <dd><a href="{{ route('g.loans.show', [$group, $transaction->loan]) }}">{{ $transaction->loan->reference }}</a></dd>
                    @endif

                    @if ($transaction->reference)
                        <dt>Reference</dt>
                        <dd class="mono small">{{ $transaction->reference }}</dd>
                    @endif

                    @if ($transaction->description)
                        <dt>Description</dt>
                        <dd>{{ $transaction->description }}</dd>
                    @endif

                    <dt>Submitted by</dt>
                    <dd>{{ $transaction->creator?->name }} · {{ $transaction->created_at->format('j M Y H:i') }}</dd>

                    @if ($transaction->verified_at)
                        <dt>{{ $transaction->isVerified() ? 'Verified by' : 'Rejected by' }}</dt>
                        <dd>{{ $transaction->verifier?->name }} · {{ $transaction->verified_at->format('j M Y H:i') }}</dd>
                    @endif

                    @if ($transaction->rejection_reason)
                        <dt>Reason</dt>
                        <dd>{{ $transaction->rejection_reason }}</dd>
                    @endif
                </dl>
            </div>

            @foreach ($transaction->journalEntries as $entry)
                <div class="card">
                    <div class="card-head">
                        <h2>
                            <a href="{{ route('g.ledger.show', [$group, $entry]) }}">{{ $entry->entry_number }}</a>
                            @if ($entry->reverses_entry_id) <span class="badge badge-warn">reversal</span> @endif
                        </h2>
                        <x-status :value="$entry->status" />
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Account</th>
                                <th>Cost center</th>
                                <th class="num">Debit</th>
                                <th class="num">Credit</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($entry->lines as $line)
                                <tr>
                                    <td>{{ $line->account->label() }}</td>
                                    <td class="small muted">{{ $line->costCenter?->code ?? '—' }}</td>
                                    <td class="num">@if ($line->isDebit())<x-amount :value="$line->debit" :currency="$line->currency" />@endif</td>
                                    <td class="num">@if (! $line->isDebit())<x-amount :value="$line->credit" :currency="$line->currency" />@endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="stack">
            @if ($transaction->hasChainEvidence())
                <div class="card">
                    <h3>Blockchain evidence</h3>
                    <dl class="deflist">
                        <dt>Network</dt>
                        <dd>{{ $transaction->network }}</dd>
                        <dt>Hash</dt>
                        <dd class="mono small">
                            @if ($transaction->explorerUrl())
                                <a href="{{ $transaction->explorerUrl() }}" target="_blank" rel="noreferrer noopener">
                                    View on blockchain explorer
                                </a>
                                <br>
                            @endif
                            <span class="truncate">{{ $transaction->tx_hash }}</span>
                        </dd>
                        <dt>Server check</dt>
                        <dd>
                            @if ($transaction->chain_verified_at)
                                <span class="badge badge-ok">confirmed</span>
                                {{ $transaction->chain_verified_at->format('j M Y H:i') }}
                            @else
                                <span class="badge badge-warn">not confirmed</span>
                            @endif
                        </dd>
                        @if ($transaction->chain_evidence['reason'] ?? null)
                            <dt>Note</dt>
                            <dd class="small">{{ $transaction->chain_evidence['reason'] }}</dd>
                        @endif
                    </dl>
                </div>
            @endif

            <div class="card">
                <h3>Receipts</h3>
                @forelse ($transaction->receipts as $receipt)
                    <p class="small" style="margin-bottom:0.35rem">
                        <a href="{{ route('g.receipts.show', [$group, $receipt]) }}">{{ $receipt->original_filename }}</a>
                        <br><span class="muted">{{ $receipt->humanSize() }} · sha256 {{ substr($receipt->sha256, 0, 12) }}…</span>
                    </p>
                @empty
                    <p class="small muted">No receipts attached.</p>
                @endforelse

                @can('attachReceipt', $transaction)
                    <form method="POST" action="{{ route('g.transactions.receipts.store', [$group, $transaction]) }}"
                          enctype="multipart/form-data" style="margin-top:0.6rem">
                        @csrf
                        <div class="field">
                            <input type="file" name="receipt" required
                                   accept="{{ collect(config('fund.receipts.mimes'))->map(fn ($m) => '.'.$m)->join(',') }}">
                        </div>
                        <button class="btn btn-small">Attach</button>
                    </form>
                @endcan
            </div>

            @if ($groupContext->isAdmin() && $transaction->isPending())
                <div class="card">
                    <h3>Reject</h3>
                    <form method="POST" action="{{ route('g.transactions.reject', [$group, $transaction]) }}">
                        @csrf
                        <div class="field">
                            <label for="reason">Reason</label>
                            <textarea id="reason" name="reason" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-small">Reject transaction</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
