<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\ChartOfAccounts;
use App\Domain\Accounting\EntryDraft;
use App\Domain\Accounting\Exceptions\PostingException;
use App\Domain\Loans\RepaymentAllocator;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * A member repays a loan.
 *
 *   Debit  treasury              (the asset arrives)
 *   Credit Loans Receivable      (principal, attributed to the member)
 *   Credit Loan Interest Income  (interest, attributed to the member)
 *
 * The interest/principal split comes from the loan's schedule, so the accounting
 * cannot disagree with the installment record.
 */
class LoanRepaymentTemplate extends BaseTemplate
{
    public function __construct(
        ChartOfAccounts $chart,
        private readonly RepaymentAllocator $allocator,
    ) {
        parent::__construct($chart);
    }

    public function key(): string
    {
        return 'LoanRepayment';
    }

    public function build(Transaction $transaction): EntryDraft
    {
        $loan = $transaction->loan ?? throw new PostingException(
            __('exceptions.repayment_no_loan', ['id' => $transaction->getKey()])
        );

        $treasury = $this->treasury($transaction);
        $amount = $this->money($transaction);
        $member = $transaction->member?->displayName() ?? 'member';

        $allocation = $this->allocator->allocate($loan, Decimal::of((string) $transaction->amount));

        $draft = EntryDraft::make(
            $this->key(),
            Carbon::parse($transaction->occurred_on),
            $this->describe($transaction, "Loan {$loan->reference} repayment from {$member}")
        )
            ->forTransaction($transaction)
            ->debit($this->treasuryAccount($treasury), $amount, null, $treasury->name)
            ->creditIfNonZero(
                $this->chart->require($transaction->group, Account::LOANS_RECEIVABLE),
                Money::of($allocation->principal, $transaction->currency),
                $loan->costCenter,
                "Principal on loan {$loan->reference}"
            )
            ->creditIfNonZero(
                $this->chart->require($transaction->group, Account::LOAN_INTEREST_INCOME),
                Money::of($allocation->interest, $transaction->currency),
                $loan->costCenter,
                "Interest on loan {$loan->reference}"
            );

        return $this->addFee($draft, $transaction, $treasury);
    }
}
