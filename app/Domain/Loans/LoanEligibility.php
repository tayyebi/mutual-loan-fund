<?php

namespace App\Domain\Loans;

use App\Domain\Money\Decimal;

/**
 * The result of checking a loan request against the governing policy.
 *
 * The same object drives the eligibility display and the enforcement decision,
 * so what a member is shown and what the server allows can never drift apart.
 */
final class LoanEligibility
{
    /**
     * @param  list<string>  $failures
     */
    public function __construct(
        public readonly bool $eligible,
        public readonly array $failures,
        public readonly Decimal $requested,
        public readonly ?Decimal $maximum,
        public readonly Decimal $outstanding,
        public readonly int $activeLoans,
        public readonly int $maximumActiveLoans,
        public readonly int $membershipDays,
        public readonly int $requiredMembershipDays,
        public readonly string $currency,
    ) {}

    public function firstFailure(): ?string
    {
        return $this->failures[0] ?? null;
    }

    /**
     * Headroom left under the policy's maximum, after existing exposure.
     */
    public function available(): ?Decimal
    {
        if ($this->maximum === null) {
            return null;
        }

        $available = $this->maximum->minus($this->outstanding);

        return $available->isNegative() ? Decimal::zero() : $available;
    }
}
