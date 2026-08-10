<?php

namespace App\Domain\Policies;

use App\Domain\Money\Decimal;
use App\Domain\Policies\Categories\ContributionPolicy;
use App\Domain\Policies\Categories\LoanPolicy;
use App\Domain\Policies\Exceptions\PolicyValidationException;

/**
 * Validates a complete policy document. A policy that fails here cannot be
 * published, so no financial operation is ever governed by incoherent rules.
 */
class PolicyValidator
{
    /**
     * @return array<string, string> keyed by "category.field"
     */
    public function validate(PolicyConfig $config): array
    {
        $errors = [];

        $this->validateMonetaryValues($config, $errors);
        $this->validateLoans($config->loans(), $errors);
        $this->validateContributions($config->contributions(), $errors);
        $this->validateAccounting($config, $errors);

        return $errors;
    }

    public function passes(PolicyConfig $config): bool
    {
        return $this->validate($config) === [];
    }

    /**
     * @throws PolicyValidationException
     */
    public function validateOrFail(PolicyConfig $config): void
    {
        $errors = $this->validate($config);

        if ($errors !== []) {
            throw new PolicyValidationException($errors);
        }
    }

    /**
     * Every monetary and rate value must be a well-formed, non-negative decimal.
     *
     * @param  array<string, string>  $errors
     */
    private function validateMonetaryValues(PolicyConfig $config, array &$errors): void
    {
        foreach ($config->categories() as $key => $category) {
            foreach ($category::fields() as $field) {
                if (! $field->isDecimal()) {
                    continue;
                }

                $value = $category->get($field->key);

                if ($value === null) {
                    if (! $field->nullable) {
                        $errors["{$key}.{$field->key}"] = __('exceptions.policy_field_required', ['field' => $field->label]);
                    }

                    continue;
                }

                if (! Decimal::isValid((string) $value)) {
                    $errors["{$key}.{$field->key}"] = __('exceptions.policy_field_must_be_number', ['field' => $field->label]);

                    continue;
                }

                if (Decimal::of((string) $value)->isNegative()) {
                    $errors["{$key}.{$field->key}"] = __('exceptions.policy_field_cannot_be_negative', ['field' => $field->label]);
                }
            }
        }
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function validateLoans(LoanPolicy $loans, array &$errors): void
    {
        $rateMin = Decimal::of((string) config('fund.policy_bounds.interest_rate_min', '0'));
        $rateMax = Decimal::of((string) config('fund.policy_bounds.interest_rate_max', '100'));
        $rate = $loans->interestRate();

        if ($rate->lessThan($rateMin) || $rate->greaterThan($rateMax)) {
            $errors['loans.interest_rate'] = __('exceptions.interest_rate_out_of_range', [
                'min' => $rateMin->format(2),
                'max' => $rateMax->format(2),
            ]);
        }

        $minTerm = $loans->minimumTermMonths();
        $maxTerm = $loans->maximumTermMonths();
        $termCeiling = (int) config('fund.policy_bounds.max_term_months', 600);

        if ($minTerm < 1) {
            $errors['loans.minimum_term_months'] = __('exceptions.minimum_term_at_least_one');
        }

        if ($maxTerm < 1) {
            $errors['loans.maximum_term_months'] = __('exceptions.maximum_term_at_least_one');
        } elseif ($maxTerm > $termCeiling) {
            $errors['loans.maximum_term_months'] = __('exceptions.maximum_term_exceeds_ceiling', ['ceiling' => $termCeiling]);
        }

        if ($minTerm > $maxTerm) {
            $errors['loans.minimum_term_months'] = __('exceptions.minimum_term_exceeds_maximum');
        }

        $minAmount = $loans->minimumAmount();
        $maxAmount = $loans->maximumAmount();

        if ($maxAmount === null) {
            $errors['loans.maximum_amount'] = __('exceptions.maximum_loan_amount_required');
        } else {
            if ($maxAmount->isZero() && $loans->enabled()) {
                $errors['loans.maximum_amount'] = __('exceptions.maximum_loan_amount_must_be_positive');
            }

            if ($minAmount->greaterThan($maxAmount)) {
                $errors['loans.minimum_amount'] = __('exceptions.minimum_loan_amount_exceeds_maximum');
            }
        }

        if ($loans->maximumActiveLoans() < 1) {
            $errors['loans.maximum_active_loans'] = __('exceptions.at_least_one_active_loan');
        }

        if ($loans->minimumMembershipDays() < 0) {
            $errors['loans.minimum_membership_days'] = __('exceptions.minimum_membership_days_negative');
        }

        if (! array_key_exists($loans->interestMethod(), LoanPolicy::INTEREST_METHODS)) {
            $errors['loans.interest_method'] = __('exceptions.unknown_interest_method');
        }

        // A rate with no method (or a method with no rate) is contradictory and
        // would make loan schedules ambiguous.
        if ($loans->interestMethod() === 'none' && $rate->isPositive()) {
            $errors['loans.interest_method'] = __('exceptions.interest_rate_needs_method');
        }

        if ($loans->interestMethod() !== 'none' && $rate->isZero()) {
            $errors['loans.interest_rate'] = __('exceptions.interest_method_needs_rate');
        }
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function validateContributions(ContributionPolicy $contributions, array &$errors): void
    {
        $min = $contributions->minimumAmount();
        $max = $contributions->maximumAmount();

        if ($max !== null && $min->greaterThan($max)) {
            $errors['contributions.minimum_amount'] = __('exceptions.minimum_contribution_exceeds_maximum');
        }

        if ($max !== null && $max->isZero()) {
            $errors['contributions.maximum_amount'] = __('exceptions.maximum_contribution_must_be_positive');
        }
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function validateAccounting(PolicyConfig $config, array &$errors): void
    {
        $currency = $config->accounting()->functionalCurrency();
        $supported = array_keys((array) config('fund.currencies', []));

        if (! in_array($currency, $supported, true)) {
            $errors['accounting.functional_currency'] = __('exceptions.functional_currency_unsupported');
        }

        if ($currency === config('fund.gold_unit')) {
            $errors['accounting.functional_currency'] =
                __('exceptions.gold_unit_cannot_be_functional_currency');
        }
    }
}
