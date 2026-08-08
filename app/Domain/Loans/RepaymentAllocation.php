<?php

namespace App\Domain\Loans;

use App\Domain\Money\Decimal;

/**
 * How one repayment splits between interest and principal, and across the
 * loan's installments.
 */
final class RepaymentAllocation
{
    /**
     * @param  array<int, Decimal>  $installments  installment id => amount applied
     */
    public function __construct(
        public readonly Decimal $principal,
        public readonly Decimal $interest,
        public readonly array $installments = [],
    ) {}

    public function total(): Decimal
    {
        return $this->principal->plus($this->interest);
    }

    public function hasInterest(): bool
    {
        return $this->interest->isPositive();
    }
}
