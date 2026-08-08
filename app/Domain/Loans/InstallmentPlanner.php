<?php

namespace App\Domain\Loans;

use App\Domain\Money\Decimal;
use App\Domain\Policies\Categories\LoanPolicy;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Support\Carbon;

/**
 * Builds a loan's repayment schedule from the policy version that governs it.
 *
 * Interest is calculated once, at disbursement, under the loan's own policy. A
 * later policy publishing a different rate cannot change an existing schedule.
 */
class InstallmentPlanner
{
    /**
     * @return list<LoanInstallment>
     */
    public function plan(Loan $loan, ?Carbon $firstDueDate = null): array
    {
        $policy = $loan->policyVersion->toPolicyConfig()->loans();
        $principal = Decimal::of((string) $loan->principal);
        $months = max(1, (int) $loan->term_months);
        $start = $firstDueDate ?? Carbon::parse($loan->disbursed_at ?? now())->addMonthNoOverflow();

        $interestTotal = $this->totalInterest($policy, $principal, $months, (string) $loan->interest_rate);

        // The last installment absorbs the rounding residual, so the schedule
        // always sums to exactly the principal and interest owed.
        $principalPerMonth = $principal->dividedBy($months);
        $interestPerMonth = $interestTotal->dividedBy($months);

        $installments = [];
        $principalAllocated = Decimal::zero();
        $interestAllocated = Decimal::zero();

        for ($i = 1; $i <= $months; $i++) {
            $isLast = $i === $months;

            $principalPart = $isLast ? $principal->minus($principalAllocated) : $principalPerMonth;
            $interestPart = $isLast ? $interestTotal->minus($interestAllocated) : $interestPerMonth;

            $principalAllocated = $principalAllocated->plus($principalPart);
            $interestAllocated = $interestAllocated->plus($interestPart);

            $installments[] = new LoanInstallment([
                'group_id' => $loan->group_id,
                'loan_id' => $loan->getKey(),
                'sequence' => $i,
                'due_date' => $start->copy()->addMonthsNoOverflow($i - 1),
                'principal_amount' => $principalPart->toString(),
                'interest_amount' => $interestPart->toString(),
                'amount' => $principalPart->plus($interestPart)->toString(),
                'paid_amount' => '0',
                'status' => LoanInstallment::STATUS_PENDING,
            ]);
        }

        return $installments;
    }

    /**
     * Interest over the whole term.
     *
     * "flat" charges the annual rate on the original principal for the term.
     * "declining" charges it on the balance outstanding each month, which is
     * less for the same nominal rate.
     */
    private function totalInterest(LoanPolicy $policy, Decimal $principal, int $months, string $loanRate): Decimal
    {
        $rate = Decimal::of($loanRate, Decimal::RATE_SCALE);

        if ($rate->isZero() || $policy->interestMethod() === 'none') {
            return Decimal::zero();
        }

        $monthlyRate = $rate->dividedBy('1200', Decimal::RATE_SCALE);

        if ($policy->interestMethod() === 'flat') {
            return $principal->times($monthlyRate)->times((string) $months);
        }

        // Declining balance: interest each month on what is still owed.
        $balance = $principal;
        $principalPerMonth = $principal->dividedBy($months);
        $total = Decimal::zero();

        for ($i = 1; $i <= $months; $i++) {
            $total = $total->plus($balance->times($monthlyRate));
            $balance = $i === $months ? Decimal::zero() : $balance->minus($principalPerMonth);
        }

        return $total;
    }
}
