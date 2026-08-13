@extends('layouts.app')
@section('title', __('loans.show.title', ['reference' => $loan->reference]))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="@surface('loan.index', $group)">{{ __('loans.show.breadcrumb') }}</a></p>
            <h1>{{ __('loans.show.title', ['reference' => $loan->reference]) }}</h1>
            <p class="muted small">
                <x-status :value="$loan->status" />
                {{ $loan->member?->displayName() }}
                · {{ __('loans.show.governed_by') }}
                {{-- The version document itself is administrative; a member
                     reads the rules in force at u.fund.rules instead. --}}
                @surfaces('policy.show')
                    <a href="@surface('policy.show', $group, $loan->policyVersion->version)">v{{ $loan->policyVersion->version }}</a>
                @else
                    <a href="{{ route('u.fund.rules', $group) }}">v{{ $loan->policyVersion->version }}</a>
                @endsurfaces
            </p>
        </div>

        <div class="actions">
            @if (\Illuminate\Support\Facades\Gate::allows('approve', $loan) && \App\Domain\Access\SurfaceRoute::serves('loan.approve'))
                <form method="POST" action="@surface('loan.approve', $group, $loan)">
                    @csrf
                    <button class="btn btn-primary">{{ __('loans.show.approve_button') }}</button>
                </form>
            @endif
            @if (\Illuminate\Support\Facades\Gate::allows('cancel', $loan) && \App\Domain\Access\SurfaceRoute::serves('loan.cancel'))
                <form method="POST" action="@surface('loan.cancel', $group, $loan)">
                    @csrf
                    <button class="btn">{{ __('loans.show.cancel_button') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-side">
        <div class="stack">
            <div class="card">
                <h2>{{ __('loans.show.terms_heading') }}</h2>
                <dl class="deflist">
                    <dt>{{ __('loans.show.principal_label') }}</dt>
                    <dd><x-amount :value="$loan->principal" :currency="$loan->currency" /></dd>
                    <dt>{{ __('loans.show.interest_label') }}</dt>
                    <dd>{{ rtrim(rtrim($loan->interest_rate, '0'), '.') ?: '0' }}% ·
                        {{ \App\Domain\Policies\Categories\LoanPolicy::INTEREST_METHODS[$loan->interest_method] ?? $loan->interest_method }}</dd>
                    <dt>{{ __('loans.show.term_label') }}</dt>
                    <dd>{{ __('loans.show.term_value', ['months' => $loan->term_months]) }}</dd>
                    <dt>{{ __('loans.show.outstanding_label') }}</dt>
                    <dd><x-amount :value="$outstanding" :currency="$loan->currency" /></dd>
                    @surfaces('cost-center.show')
                        <dt>{{ __('loans.show.cost_center_label') }}</dt>
                        <dd>
                            @if ($loan->costCenter)
                                <a href="@surface('cost-center.show', $group, $loan->costCenter)">{{ $loan->costCenter->label() }}</a>
                            @else — @endif
                        </dd>
                    @endsurfaces
                    @if ($loan->purpose)
                        <dt>{{ __('loans.show.purpose_label') }}</dt>
                        <dd>{{ $loan->purpose }}</dd>
                    @endif
                    @if ($loan->approved_at)
                        <dt>{{ __('loans.show.decision_label') }}</dt>
                        <dd>
                            {{ $loan->approver?->name }} · <x-datetime :value="$loan->approved_at" />
                            @if ($loan->decision_reason)<br><span class="small muted">{{ $loan->decision_reason }}</span>@endif
                        </dd>
                    @endif
                </dl>
            </div>

            @if ($loan->installments->isNotEmpty())
                <div class="card">
                    <h2>{{ __('loans.show.schedule_heading') }}</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>{{ __('loans.show.col_seq') }}</th>
                                <th>{{ __('loans.show.col_due') }}</th>
                                <th class="num">{{ __('loans.show.col_principal') }}</th>
                                <th class="num">{{ __('loans.show.col_interest') }}</th>
                                <th class="num">{{ __('loans.show.col_total') }}</th>
                                <th class="num">{{ __('loans.show.col_paid') }}</th>
                                <th>{{ __('loans.show.col_status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($loan->installments as $installment)
                                <tr>
                                    <td class="num">{{ $installment->sequence }}</td>
                                    <td class="num"><x-datetime :value="$installment->due_date" /></td>
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
                <h2>{{ __('loans.show.transactions_heading') }}</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>{{ __('loans.show.col_date') }}</th>
                            <th>{{ __('loans.show.col_type') }}</th>
                            <th class="num">{{ __('loans.show.col_amount') }}</th>
                            <th>{{ __('loans.show.col_status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($loan->transactions as $transaction)
                            <tr>
                                <td class="num">
                                    <a href="@surface('transaction.show', $group, $transaction)">
                                        <x-datetime :value="$transaction->occurred_on" />
                                    </a>
                                </td>
                                <td class="small">{{ $transaction->typeLabel() }}</td>
                                <td class="num"><x-amount :value="$transaction->amount" :currency="$transaction->currency" /></td>
                                <td><x-status :value="$transaction->status" /></td>
                            </tr>
                        @empty
                            <x-empty colspan="4">{{ __('loans.show.transactions_empty') }}</x-empty>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            @if (\Illuminate\Support\Facades\Gate::allows('disburse', $loan) && \App\Domain\Access\SurfaceRoute::serves('loan.disburse'))
                <div class="card">
                    <h3>{{ __('loans.show.disburse_heading') }}</h3>
                    <form method="POST" action="@surface('loan.disburse', $group, $loan)">
                        @csrf
                        <div class="field">
                            <label for="treasury_id">{{ __('loans.show.from_treasury_label') }}</label>
                            <select id="treasury_id" name="treasury_id" required>
                                @foreach ($treasuries as $treasury)
                                    <option value="{{ $treasury->id }}">{{ $treasury->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="occurred_on">{{ __('loans.show.date_paid_label') }}</label>
                            <input id="occurred_on" name="occurred_on" type="date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="tx_hash">{{ __('loans.show.tx_hash_label') }}</label>
                            <input id="tx_hash" name="tx_hash" type="text">
                        </div>
                        <button class="btn btn-primary btn-small">{{ __('loans.show.disburse_button') }}</button>
                        <span class="hint">{{ __('loans.show.disburse_hint') }}</span>
                    </form>
                </div>
            @endif

            @can('repay', $loan)
                {{--
                    A member repays through the short wizard (Member\LoanRepayWizardController)
                    rather than this dense form — MemberSurface is the only one that declares
                    the 'loan.repay.start' intent, which is why the surface guard below is the
                    right check: an administrator recording a repayment on someone's behalf
                    keeps the original all-in-one form.
                --}}
                @surfaces('loan.repay.start')
                    <div class="card">
                        <h3>{{ __('loans.show.repay_start_heading') }}</h3>
                        <p class="small muted">{{ __('loans.show.repay_start_intro') }}</p>
                        <a class="btn btn-primary btn-small" href="@surface('loan.repay.start', $group, $loan)">
                            {{ __('loans.show.repay_start_button') }}
                        </a>
                    </div>
                @else
                    <div class="card">
                        <h3>{{ __('loans.show.repay_heading') }}</h3>
                        <form method="POST" action="@surface('loan.repay', $group, $loan)">
                            @csrf
                            <div class="field">
                                <label for="repay_treasury">{{ __('loans.show.into_treasury_label') }}</label>
                                <select id="repay_treasury" name="treasury_id" required>
                                    @foreach ($treasuries as $treasury)
                                        <option value="{{ $treasury->id }}">{{ $treasury->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="repay_amount">{{ __('loans.show.repay_amount_label', ['currency' => $loan->currency]) }}</label>
                                <input id="repay_amount" name="amount" type="text" inputmode="decimal" required>
                            </div>
                            <div class="field">
                                <label for="repay_date">{{ __('loans.show.repay_date_label') }}</label>
                                <input id="repay_date" name="occurred_on" type="date" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="field">
                                <label for="repay_hash">{{ __('loans.show.repay_hash_label') }}</label>
                                <input id="repay_hash" name="tx_hash" type="text">
                            </div>
                            <button class="btn btn-primary btn-small">{{ __('loans.show.repay_button') }}</button>
                            <span class="hint">{{ __('loans.show.repay_hint') }}</span>
                        </form>
                    </div>
                @endsurfaces
            @endcan

            @if (\Illuminate\Support\Facades\Gate::allows('approve', $loan) && \App\Domain\Access\SurfaceRoute::serves('loan.reject'))
                <div class="card">
                    <h3>{{ __('loans.show.reject_heading') }}</h3>
                    <form method="POST" action="@surface('loan.reject', $group, $loan)">
                        @csrf
                        <div class="field">
                            <label for="reason">{{ __('loans.show.reason_label') }}</label>
                            <textarea id="reason" name="reason" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-small">{{ __('loans.show.reject_button') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
