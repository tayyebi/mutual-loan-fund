@extends('layouts.app')
@section('title', __('loans.create.title'))

@section('content')
    <div class="page-head">
        <div>
            <p class="breadcrumb"><a href="@surface('loan.index', $group)">{{ __('loans.create.breadcrumb') }}</a></p>
            <h1>{{ __('loans.create.heading') }}</h1>
            <p class="muted small">
                {{ __('loans.create.intro_prefix') }} <a href="{{ route('u.fund.rules', $group) }}">v{{ $policy->version }}</a>.
                {{ __('loans.create.intro_suffix') }}
            </p>
        </div>
    </div>

    <div class="grid grid-side">
        <div class="card">
            @unless ($loanPolicy->enabled())
                <div class="alert alert-warn">{{ __('loans.create.not_lending_notice') }}</div>
            @endunless

            <form method="GET" class="filters" style="margin-bottom:1rem">
                <div class="field">
                    <label for="check_amount">{{ __('loans.create.amount_label') }}</label>
                    <input id="check_amount" name="amount" type="text" inputmode="decimal" value="{{ $amount }}">
                </div>
                <div class="field">
                    <label for="check_currency">{{ __('loans.create.currency_label') }}</label>
                    <select id="check_currency" name="currency">
                        @foreach ($currencies as $code)
                            <option value="{{ $code }}" @selected($currency === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="check_term">{{ __('loans.create.term_label') }}</label>
                    <input id="check_term" name="term_months" type="number" min="1" value="{{ $term }}">
                </div>
                <button class="btn">{{ __('loans.create.check_button') }}</button>
            </form>

            <div class="card" style="background: var(--surface-2);">
                <h3 style="margin-top:0">{{ __('loans.create.eligibility_heading') }}</h3>
                <dl class="deflist">
                    <dt>{{ __('loans.create.requested_label') }}</dt>
                    <dd><x-amount :value="$eligibility->requested" :currency="$eligibility->currency" /></dd>

                    <dt>{{ __('loans.create.maximum_label') }}</dt>
                    <dd>
                        @if ($eligibility->maximum)
                            <x-amount :value="$eligibility->maximum" :currency="$eligibility->currency" />
                        @else
                            <span class="muted">{{ __('loans.create.unlimited') }}</span>
                        @endif
                    </dd>

                    <dt>{{ __('loans.create.outstanding_label') }}</dt>
                    <dd><x-amount :value="$eligibility->outstanding" :currency="$eligibility->currency" /></dd>

                    <dt>{{ __('loans.create.active_loans_label') }}</dt>
                    <dd>{{ __('loans.create.active_loans_value', ['active' => $eligibility->activeLoans, 'max' => $eligibility->maximumActiveLoans]) }}</dd>

                    <dt>{{ __('loans.create.membership_label') }}</dt>
                    <dd>{{ __('loans.create.membership_value', ['days' => $eligibility->membershipDays, 'required' => $eligibility->requiredMembershipDays]) }}</dd>

                    <dt>{{ __('loans.create.eligible_label') }}</dt>
                    <dd>
                        @if ($eligibility->eligible)
                            <span class="badge badge-ok">{{ __('loans.create.eligible_yes') }}</span>
                        @else
                            <span class="badge badge-danger">{{ __('loans.create.eligible_no') }}</span>
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

            <form method="POST" action="@surface('loan.store', $group)">
                @csrf
                <div class="field-row field-row-3">
                    <div class="field">
                        <label for="amount">{{ __('loans.create.amount_label') }}</label>
                        <input id="amount" name="amount" type="text" inputmode="decimal" value="{{ old('amount', (string) $amount) }}" required>
                    </div>
                    <div class="field">
                        <label for="currency">{{ __('loans.create.currency_label') }}</label>
                        <select id="currency" name="currency" required>
                            @foreach ($currencies as $code)
                                <option value="{{ $code }}" @selected(old('currency', $currency) === $code)>{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="term_months">{{ __('loans.create.term_label') }}</label>
                        <input id="term_months" name="term_months" type="number" min="{{ $loanPolicy->minimumTermMonths() }}"
                               max="{{ $loanPolicy->maximumTermMonths() }}" value="{{ old('term_months', $term) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="purpose">{{ __('loans.create.purpose_label') }}</label>
                    <textarea id="purpose" name="purpose">{{ old('purpose') }}</textarea>
                </div>

                <button class="btn btn-primary">{{ __('loans.create.submit') }}</button>
            </form>
        </div>

        <div class="card">
            <h3>{{ __('loans.create.policy_heading', ['version' => $policy->version]) }}</h3>
            <dl class="deflist">
                <dt>{{ __('loans.create.interest_label') }}</dt>
                <dd>{{ $loanPolicy->interestRate()->format(2) }}% ({{ \App\Domain\Policies\Categories\LoanPolicy::INTEREST_METHODS[$loanPolicy->interestMethod()] }})</dd>
                <dt>{{ __('loans.create.amount_range_label') }}</dt>
                <dd>
                    {{ $loanPolicy->minimumAmount()->format(2) }} –
                    {{ $loanPolicy->maximumAmount()?->format(2) ?? __('loans.create.unlimited') }}
                </dd>
                <dt>{{ __('loans.create.term_range_label') }}</dt>
                <dd>{{ __('loans.create.term_range_value', ['min' => $loanPolicy->minimumTermMonths(), 'max' => $loanPolicy->maximumTermMonths()]) }}</dd>
                <dt>{{ __('loans.create.active_loans_policy_label') }}</dt>
                <dd>{{ $loanPolicy->maximumActiveLoans() }}</dd>
                <dt>{{ __('loans.create.membership_policy_label') }}</dt>
                <dd>{{ __('loans.create.membership_policy_value', ['days' => $loanPolicy->minimumMembershipDays()]) }}</dd>
                <dt>{{ __('loans.create.early_repayment_label') }}</dt>
                <dd>{{ $loanPolicy->allowsEarlyRepayment() ? __('loans.create.early_repayment_allowed') : __('loans.create.early_repayment_not_allowed') }}</dd>
            </dl>
            <p class="small muted" style="margin-top:0.8rem">
                {{ __('loans.create.footer_note') }}
            </p>
        </div>
    </div>
@endsection
