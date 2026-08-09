@extends('layouts.app')
@section('title', 'Request a loan')

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="{{ route('g.loans.index', $group) }}">Loans</a></p>
            <h1>Request a loan</h1>
            <p class="muted small">
                Under policy <a href="{{ route('g.policies.show', [$group, $policy->version]) }}">v{{ $policy->version }}</a>.
                The rules below are enforced, not just displayed.
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            @unless ($loanPolicy->enabled())
                <div class="alert alert-warn">This fund is not currently lending.</div>
            @endunless

            <form method="GET" class="filters" style="margin-bottom:1rem">
                <div class="field">
                    <label for="check_amount">Amount</label>
                    <input id="check_amount" name="amount" type="text" inputmode="decimal" value="{{ $amount }}">
                </div>
                <div class="field">
                    <label for="check_currency">Currency</label>
                    <select id="check_currency" name="currency">
                        @foreach ($currencies as $code)
                            <option value="{{ $code }}" @selected($currency === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="check_term">Term (months)</label>
                    <input id="check_term" name="term_months" type="number" min="1" value="{{ $term }}">
                </div>
                <button class="btn">Check eligibility</button>
            </form>

            <div class="card" style="background: var(--surface-2);">
                <h3 style="margin-top:0">Eligibility</h3>
                <dl class="deflist">
                    <dt>Requested</dt>
                    <dd><x-amount :value="$eligibility->requested" :currency="$eligibility->currency" /></dd>

                    <dt>Maximum</dt>
                    <dd>
                        @if ($eligibility->maximum)
                            <x-amount :value="$eligibility->maximum" :currency="$eligibility->currency" />
                        @else
                            <span class="muted">unlimited</span>
                        @endif
                    </dd>

                    <dt>Outstanding</dt>
                    <dd><x-amount :value="$eligibility->outstanding" :currency="$eligibility->currency" /></dd>

                    <dt>Active loans</dt>
                    <dd>{{ $eligibility->activeLoans }} of {{ $eligibility->maximumActiveLoans }}</dd>

                    <dt>Membership</dt>
                    <dd>{{ $eligibility->membershipDays }} days (needs {{ $eligibility->requiredMembershipDays }})</dd>

                    <dt>Eligible</dt>
                    <dd>
                        @if ($eligibility->eligible)
                            <span class="badge badge-ok">Yes</span>
                        @else
                            <span class="badge badge-danger">No</span>
                        @endif
                    </dd>
                </dl>

                @unless ($eligibility->eligible)
                    <ul class="small" style="margin: 0.6rem 0 0; padding-left: 1.1rem;">
                        @foreach ($eligibility->failures as $failure)
                            <li>{{ $failure }}</li>
                        @endforeach
                    </ul>
                @endunless
            </div>

            <hr class="divider">

            <form method="POST" action="{{ route('g.loans.store', $group) }}">
                @csrf
                <div class="field-row field-row-3">
                    <div class="field">
                        <label for="amount">Amount</label>
                        <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount', (string) $amount) }}" required>
                    </div>
                    <div class="field">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency" required>
                            @foreach ($currencies as $code)
                                <option value="{{ $code }}" @selected(old('currency', $currency) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="term_months">Term (months)</label>
                        <input id="term_months" name="term_months" type="number" min="{{ $loanPolicy->minimumTermMonths() }}"
                               max="{{ $loanPolicy->maximumTermMonths() }}" value="{{ old('term_months', $term) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="purpose">Purpose</label>
                    <textarea id="purpose" name="purpose">{{ old('purpose') }}</textarea>
                </div>

                <button class="btn btn-primary">Request loan</button>
            </form>
        </div>

        <div class="card">
            <h3>Policy v{{ $policy->version }}</h3>
            <dl class="deflist">
                <dt>Interest</dt>
                <dd>{{ $loanPolicy->interestRate()->format(2) }}% ({{ \App\Domain\Policies\Categories\LoanPolicy::INTEREST_METHODS[$loanPolicy->interestMethod()] }})</dd>
                <dt>Amount</dt>
                <dd>
                    {{ $loanPolicy->minimumAmount()->format(2) }} –
                    {{ $loanPolicy->maximumAmount()?->format(2) ?? 'unlimited' }}
                </dd>
                <dt>Term</dt>
                <dd>{{ $loanPolicy->minimumTermMonths() }}–{{ $loanPolicy->maximumTermMonths() }} months</dd>
                <dt>Active loans</dt>
                <dd>{{ $loanPolicy->maximumActiveLoans() }}</dd>
                <dt>Membership</dt>
                <dd>{{ $loanPolicy->minimumMembershipDays() }} days minimum</dd>
                <dt>Early repayment</dt>
                <dd>{{ $loanPolicy->allowsEarlyRepayment() ? 'Allowed' : 'Not allowed' }}</dd>
            </dl>
            <p class="small muted" style="margin-top:0.8rem">
                If the administrator publishes a new policy later, this loan keeps these
                rules.
            </p>
        </div>
    </div>
@endsection
