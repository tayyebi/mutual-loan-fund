<?php

namespace App\Domain\Loans;

use App\Domain\Money\Decimal;
use App\Models\Loan;
use App\Models\LoanInstallment;

/**
 * Splits a repayment into interest and principal.
 *
 * Payments are applied to the oldest outstanding installment first, and within
 * an installment to interest before principal. The split is derived from the
 * schedule rather than entered by hand, so the same payment always produces the
 * same accounting.
 */
class RepaymentAllocator
{
    public function allocate(Loan $loan, Decimal $payment): RepaymentAllocation
    {
        $remaining = $payment;
        $principal = Decimal::zero();
        $interest = Decimal::zero();
        $applied = [];

        $installments = $loan->installments()
            ->whereIn('status', [
                LoanInstallment::STATUS_PENDING,
                LoanInstallment::STATUS_PARTIALLY_PAID,
                LoanInstallment::STATUS_OVERDUE,
            ])
            ->orderBy('sequence')
            ->get();

        foreach ($installments as $installment) {
            if (! $remaining->isPositive()) {
                break;
            }

            $due = $installment->remaining();

            if (! $due->isPositive()) {
                continue;
            }

            $take = $remaining->min($due);

            // Interest already covered by earlier partial payments on this
            // installment, so a second payment does not charge it twice.
            $paidSoFar = Decimal::of((string) $installment->paid_amount);
            $installmentInterest = Decimal::of((string) $installment->interest_amount);
            $interestPaid = $paidSoFar->min($installmentInterest);
            $interestDue = $installmentInterest->minus($interestPaid);

            $toInterest = $take->min($interestDue);
            $toPrincipal = $take->minus($toInterest);

            $interest = $interest->plus($toInterest);
            $principal = $principal->plus($toPrincipal);
            $applied[$installment->getKey()] = $take;

            $remaining = $remaining->minus($take);
        }

        // Anything beyond the schedule reduces principal: an early repayment, or
        // a loan recorded without installments.
        if ($remaining->isPositive()) {
            $principal = $principal->plus($remaining);
        }

        return new RepaymentAllocation($principal, $interest, $applied);
    }
}
