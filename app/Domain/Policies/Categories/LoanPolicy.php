<?php

namespace App\Domain\Policies\Categories;

use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyCategory;
use App\Domain\Policies\PolicyField;

class LoanPolicy extends PolicyCategory
{
    public const INTEREST_METHODS = [
        'none' => 'No interest',
        'flat' => 'Flat on principal',
        'declining' => 'Declining balance',
    ];

    public static function key(): string
    {
        return 'loans';
    }

    public static function label(): string
    {
        return 'Loans';
    }

    public static function fields(): array
    {
        return [
            'enabled' => PolicyField::bool('enabled', 'Loans enabled', true),
            'minimum_amount' => PolicyField::money('minimum_amount', 'Minimum amount', '0'),
            'maximum_amount' => PolicyField::money('maximum_amount', 'Maximum amount', '2500'),
            'interest_rate' => PolicyField::rate('interest_rate', 'Interest rate', '0'),
            'interest_method' => PolicyField::enum('interest_method', 'Interest method', self::INTEREST_METHODS, 'none'),
            'minimum_term_months' => PolicyField::integer('minimum_term_months', 'Minimum term', 1, 'months'),
            'maximum_term_months' => PolicyField::integer('maximum_term_months', 'Maximum term', 12, 'months'),
            'maximum_active_loans' => PolicyField::integer('maximum_active_loans', 'Maximum active loans', 1),
            'minimum_membership_days' => PolicyField::integer('minimum_membership_days', 'Minimum membership', 30, 'days'),
            'early_repayment_allowed' => PolicyField::bool('early_repayment_allowed', 'Early repayment allowed', true),
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
