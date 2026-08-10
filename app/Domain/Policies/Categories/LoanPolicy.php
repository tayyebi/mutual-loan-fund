<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class LoanPolicy extends PolicyCategory
{
    public static function key(): string
    {
        return 'loans';
    }

    public static function label(): string
    {
        return __('policies.fields.categories.loans');
    }

    public static function interestMethods(): array
    {
        return [
            'none' => __('policies.fields.loans.interest_method_none'),
            'flat' => __('policies.fields.loans.interest_method_flat'),
            'declining' => __('policies.fields.loans.interest_method_declining'),
        ];
    }

    public static function fields(): array
    {
        return [
            'enabled' => PolicyField::bool('enabled', __('policies.fields.loans.enabled'), true),
            'minimum_amount' => PolicyField::money('minimum_amount', __('policies.fields.loans.minimum_amount'), '0'),
            'maximum_amount' => PolicyField::money('maximum_amount', __('policies.fields.loans.maximum_amount'), '2500'),
            'interest_rate' => PolicyField::rate('interest_rate', __('policies.fields.loans.interest_rate'), '0'),
            'interest_method' => PolicyField::enum('interest_method', __('policies.fields.loans.interest_method'), self::interestMethods(), 'none'),
            'minimum_term_months' => PolicyField::integer('minimum_term_months', __('policies.fields.loans.minimum_term_months'), 1, 'months'),
            'maximum_term_months' => PolicyField::integer('maximum_term_months', __('policies.fields.loans.maximum_term_months'), 12, 'months'),
            'maximum_active_loans' => PolicyField::integer('maximum_active_loans', __('policies.fields.loans.maximum_active_loans'), 1),
            'minimum_membership_days' => PolicyField::integer('minimum_membership_days', __('policies.fields.loans.minimum_membership_days'), 30, 'days'),
            'early_repayment_allowed' => PolicyField::bool('early_repayment_allowed', __('policies.fields.loans.early_repayment_allowed'), true),
        ];
    }

    public function minimumAmount(): Decimal
    {
        return $this->decimal('minimum_amount') ?? Decimal::zero();
    }

    public function maximumAmount(): ?Decimal
    {
        return $this->decimal('maximum_amount');
    }

    public function interestRate(): Decimal
    {
        return $this->decimal('interest_rate') ?? Decimal::zero();
    }

    public function interestMethod(): string
    {
        return (string) $this->get('interest_method', 'none');
    }

    public function chargesInterest(): bool
    {
        return $this->interestMethod() !== 'none' && $this->interestRate()->isPositive();
    }

    public function minimumTermMonths(): int
    {
        return $this->integer('minimum_term_months') ?? 1;
    }

    public function maximumTermMonths(): int
    {
        return $this->integer('maximum_term_months') ?? 12;
    }

    public function maximumActiveLoans(): int
    {
        return $this->integer('maximum_active_loans') ?? 1;
    }

    public function minimumMembershipDays(): int
    {
        return $this->integer('minimum_membership_days') ?? 0;
    }

    public function allowsEarlyRepayment(): bool
    {
        return $this->bool('early_repayment_allowed');
    }
}
