<?php

namespace App\Domain\Transactions\Events;

use App\Models\Transaction;
use App\Models\User;

/**
 * Raised inside the database transaction that verifies and posts a financial
 * event.
 *
 * Listeners run synchronously and their work commits — or rolls back — with the
 * posting itself. This is how the loan domain learns about disbursements and
 * repayments without the transaction layer depending on it.
 */
class TransactionVerified
{
    public function __construct(
        public readonly Transaction $transaction,
        public readonly User $actor,
    ) {}
}
