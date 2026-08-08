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
                        $errors["{$key}.{$field->key}"] = "{$field->label} is required.";
                    }

                    continue;
                }

                if (! Decimal::isValid((string) $value)) {
                    $errors["{$key}.{$field->key}"] = "{$field->label} must be a number.";

                    continue;
                }

                if (Decimal::of((string) $value)->isNegative()) {
                    $errors["{$key}.{$field->key}"] = "{$field->label} cannot be negative.";
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
            $errors['loans.interest_rate'] = sprintf(
                'Interest rate must be between %s%% and %s%%.',
                $rateMin->format(2),
                $rateMax->format(2)
            );
        }

        $minTerm = $loans->minimumTermMonths();
        $maxTerm = $loans->maximumTermMonths();
        $termCeiling = (int) config('fund.policy_bounds.max_term_months', 600);

        if ($minTerm < 1) {
            $errors['loans.minimum_term_months'] = 'Minimum term must be at least 1 month.';
        }

        if ($maxTerm < 1) {
            $errors['loans.maximum_term_months'] = 'Maximum term must be at least 1 month.';
        } elseif ($maxTerm > $termCeiling) {
            $errors['loans.maximum_term_months'] = "Maximum term cannot exceed {$termCeiling} months.";
        }

        if ($minTerm > $maxTerm) {
            $errors['loans.minimum_term_months'] = 'Minimum term cannot exceed the maximum term.';
        }

        $minAmount = $loans->minimumAmount();
        $maxAmount = $loans->maximumAmount();

        if ($maxAmount === null) {
            $errors['loans.maximum_amount'] = 'A maximum loan amount is required.';
        } else {
            if ($maxAmount->isZero() && $loans->enabled()) {
                $errors['loans.maximum_amount'] = 'Maximum loan amount must be greater than zero while loans are enabled.';
            }

            if ($minAmount->greaterThan($maxAmount)) {
                $errors['loans.minimum_amount'] = 'Minimum loan amount cannot exceed the maximum amount.';
            }
        }

        if ($loans->maximumActiveLoans() < 1) {
            $errors['loans.maximum_active_loans'] = 'At least one active loan must be permitted.';
        }

        if ($loans->minimumMembershipDays() < 0) {
            $errors['loans.minimum_membership_days'] = 'Minimum membership days cannot be negative.';
        }

        if (! array_key_exists($loans->interestMethod(), LoanPolicy::INTEREST_METHODS)) {
            $errors['loans.interest_method'] = 'Unknown interest method.';
        }

        // A rate with no method (or a method with no rate) is contradictory and
        // would make loan schedules ambiguous.
        if ($loans->interestMethod() === 'none' && $rate->isPositive()) {
            $errors['loans.interest_method'] = 'An interest rate above zero requires an interest method.';
        }

        if ($loans->interestMethod() !== 'none' && $rate->isZero()) {
            $errors['loans.interest_rate'] = 'An interest method requires a rate above zero.';
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
            $errors['contributions.minimum_amount'] = 'Minimum contribution cannot exceed the maximum.';
        }

        if ($max !== null && $max->isZero()) {
            $errors['contributions.maximum_amount'] = 'Maximum contribution must be greater than zero, or blank for no limit.';
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
            $errors['accounting.functional_currency'] = 'Functional currency is not supported.';
        }

        if ($currency === config('fund.gold_unit')) {
            $errors['accounting.functional_currency'] =
                'The 18K gold unit is a valuation layer and cannot be a functional currency.';
        }
    }
}
