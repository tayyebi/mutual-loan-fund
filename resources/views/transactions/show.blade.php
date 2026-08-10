@extends('layouts.app')
@section('title', __('transactions.show.title', ['id' => $transaction->id]))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="@surface('transaction.index', $group)">{{ __('transactions.show.breadcrumb') }}</a></p>
            <h1>{{ $transaction->typeLabel() }} · <x-amount :value="$transaction->amount" :currency="$transaction->currency" /></h1>
            <p class="muted small">
                <x-status :value="$transaction->status" />
                <x-datetime :value="$transaction->occurred_on" />
                @if ($transaction->member) · {{ $transaction->member->displayName() }} @endif
                @if ($transaction->policyVersion)
                    · {{ __('transactions.show.policy_prefix') }}
                    @surfaces('policy.show')
                        <a href="@surface('policy.show', $group, $transaction->policyVersion->version)">v{{ $transaction->policyVersion->version }}</a>
                    @else
                        <a href="{{ route('u.fund.rules', $group) }}">v{{ $transaction->policyVersion->version }}</a>
                    @endsurfaces
                @endif
            </p>
        </div>

        @if (\App\Domain\Access\SurfaceRoute::serves('transaction.verify') && $transaction->isPending())
            <div class="actions">
                <form method="POST" action="@surface('transaction.verify', $group, $transaction)">
                    @csrf
                    <button class="btn btn-primary">{{ __('transactions.show.verify_button') }}</button>
                </form>
                @if ($transaction->hasChainEvidence())
                    <form method="POST" action="@surface('transaction.chain-check', $group, $transaction)">
                        @csrf
                        <button class="btn">{{ __('transactions.show.chain_recheck_button') }}</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if ($transaction->isPending() && $needsAdminVerification)
        <div class="alert alert-warn">
            {{ __('transactions.show.pending_notice') }}
        </div>
    @endif

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <h2>{{ __('transactions.show.details_heading') }}</h2>
                <dl class="deflist">
                    <dt>{{ __('transactions.show.treasury_label') }}</dt>
                    <dd>{{ $transaction->treasury?->name ?? '—' }}</dd>

                    @if ($transaction->counterTreasury)
                        <dt>{{ __('transactions.show.destination_label') }}</dt>
                        <dd>
                            {{ $transaction->counterTreasury->name }}
                            @if ($transaction->counter_amount)
                                — <x-amount :value="$transaction->counter_amount" :currency="$transaction->counter_currency" />
                            @endif
                        </dd>
                    @endif

                    @if ($transaction->fee_amount && (float) $transaction->fee_amount > 0)
                        <dt>{{ __('transactions.show.fee_label') }}</dt>
                        <dd><x-amount :value="$transaction->fee_amount" :currency="$transaction->fee_currency" /></dd>
                    @endif

                    @if ($transaction->loan)
                        <dt>{{ __('transactions.show.loan_label') }}</dt>
                        <dd><a href="@surface('loan.show', $group, $transaction->loan)">{{ $transaction->loan->reference }}</a></dd>
                    @endif

                    @if ($transaction->reference)
                        <dt>{{ __('transactions.show.reference_label') }}</dt>
                        <dd class="mono small">{{ $transaction->reference }}</dd>
                    @endif

                    @if ($transaction->description)
                        <dt>{{ __('transactions.show.description_label') }}</dt>
                        <dd>{{ $transaction->description }}</dd>
                    @endif

                    <dt>{{ __('transactions.show.submitted_by_label') }}</dt>
                    <dd>{{ $transaction->creator?->name }} · <x-datetime :value="$transaction->created_at" format="j M Y H:i" /></dd>

                    @if ($transaction->verified_at)
                        <dt>{{ $transaction->isVerified() ? __('transactions.show.verified_by_label') : __('transactions.show.rejected_by_label') }}</dt>
                        <dd>{{ $transaction->verifier?->name }} · <x-datetime :value="$transaction->verified_at" format="j M Y H:i" /></dd>
                    @endif

                    @if ($transaction->rejection_reason)
                        <dt>{{ __('transactions.show.reason_label') }}</dt>
                        <dd>{{ $transaction->rejection_reason }}</dd>
                    @endif
                </dl>
            </div>

            {{--
                The journal entries this transaction posted. Shown only on the
                administrator's surface: the double-entry behind a payment is
                what makes the figures reconcilable, but reading it is a
                bookkeeping skill, and a member checking their own contribution
                is served by the status and the receipt above.
            --}}
            @surfaces('ledger.show')
            @foreach ($transaction->journalEntries as $entry)
                <div class="card">
                    <div class="card-head">
                        <h2>
                            <a href="@surface('ledger.show', $group, $entry)">{{ $entry->entry_number }}</a>
                            @if ($entry->reverses_entry_id) <span class="badge badge-warn">{{ __('transactions.show.reversal_badge') }}</span> @endif
                        </h2>
                        <x-status :value="$entry->status" />
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>{{ __('transactions.show.col_account') }}</th>
                                <th>{{ __('transactions.show.col_cost_center') }}</th>
                                <th class="num">{{ __('transactions.show.col_debit') }}</th>
                                <th class="num">{{ __('transactions.show.col_credit') }}</th>
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
            @endsurfaces
        </div>

        <div class="stack">
            @if ($transaction->hasChainEvidence())
                <div class="card">
                    <h3>{{ __('transactions.show.chain_evidence_heading') }}</h3>
                    <dl class="deflist">
                        <dt>{{ __('transactions.show.network_label') }}</dt>
                        <dd>{{ $transaction->network }}</dd>
                        <dt>{{ __('transactions.show.hash_label') }}</dt>
                        <dd class="mono small">
                            @if ($transaction->explorerUrl())
                                <a href="{{ $transaction->explorerUrl() }}" target="_blank" rel="noreferrer noopener">
                                    {{ __('transactions.show.view_explorer') }}
                                </a>
                                <br>
                            @endif
                            <span class="truncate">{{ $transaction->tx_hash }}</span>
                        </dd>
                        <dt>{{ __('transactions.show.server_check_label') }}</dt>
                        <dd>
                            @if ($transaction->chain_verified_at)
                                <span class="badge badge-ok">{{ __('transactions.show.confirmed_badge') }}</span>
                                <x-datetime :value="$transaction->chain_verified_at" format="j M Y H:i" />
                            @else
                                <span class="badge badge-warn">{{ __('transactions.show.not_confirmed_badge') }}</span>
                            @endif
                        </dd>
                        @if ($transaction->chain_evidence['reason'] ?? null)
                            <dt>{{ __('transactions.show.note_label') }}</dt>
                            <dd class="small">{{ $transaction->chain_evidence['reason'] }}</dd>
                        @endif
                    </dl>
                </div>
            @endif

            <div class="card">
                <h3>{{ __('transactions.show.receipts_heading') }}</h3>
                @forelse ($transaction->receipts as $receipt)
                    <p class="small" style="margin-bottom:0.35rem">
                        <a href="@surface('receipt.show', $group, $receipt)">{{ $receipt->original_filename }}</a>
                        <br><span class="muted">{{ $receipt->humanSize() }} · sha256 {{ substr($receipt->sha256, 0, 12) }}…</span>
                    </p>
                @empty
                    <p class="small muted">{{ __('transactions.show.no_receipts') }}</p>
                @endforelse

                @can('attachReceipt', $transaction)
                    <form method="POST" action="@surface('transaction.receipts.store', $group, $transaction)"
                          enctype="multipart/form-data" style="margin-top:0.6rem">
                        @csrf
                        <div class="field">
                            <input type="file" name="receipt" required
                                   accept="{{ collect(config('fund.receipts.mimes'))->map(fn ($m) => '.'.$m)->join(',') }}">
                        </div>
                        <button class="btn btn-small">{{ __('transactions.show.attach_button') }}</button>
                    </form>
                @endcan
            </div>

            @if (\App\Domain\Access\SurfaceRoute::serves('transaction.reject') && $transaction->isPending())
                <div class="card">
                    <h3>{{ __('transactions.show.reject_heading') }}</h3>
                    <form method="POST" action="@surface('transaction.reject', $group, $transaction)">
                        @csrf
                        <div class="field">
                            <label for="reason">{{ __('transactions.show.reject_reason_label') }}</label>
                            <textarea id="reason" name="reason" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-small">{{ __('transactions.show.reject_button') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
