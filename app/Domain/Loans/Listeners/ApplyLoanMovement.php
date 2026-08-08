<?php

namespace App\Domain\Loans\Listeners;

use App\Domain\Loans\LoanService;
use App\Domain\Transactions\Events\TransactionVerified;

/**
 * Keeps loans in step with the ledger.
 *
 * The transaction layer stays unaware of loans; it announces that an event was
 * verified and this listener translates disbursements and repayments into loan
 * state, inside the same database transaction.
 */
class ApplyLoanMovement
{
    public function __construct(private readonly LoanService $loans) {}

    public function handle(TransactionVerified $event): void
    {
        $this->loans->applyVerifiedTransaction($event->transaction, $event->actor);
    }
}
