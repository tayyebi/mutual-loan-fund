<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Support\GroupContext;

class TransactionPolicy
{
    public function __construct(private readonly GroupContext $context) {}

    /**
     * Group activity is visible to every member: a mutual fund's members can see
     * what the fund did.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return $this->context->has();
    }

    public function create(User $user): bool
    {
        return $this->context->has();
    }

    /**
     * Verification is an administrative act, and never by the person who
     * submitted the transaction — a member must not be able to confirm their own
     * claim into the ledger.
     */
    public function verify(User $user, Transaction $transaction): bool
    {
        return $this->context->isAdmin()
            && $transaction->isPending()
            && (int) $transaction->created_by !== (int) $user->getKey();
    }

    public function reject(User $user, Transaction $transaction): bool
    {
        return $this->context->isAdmin() && $transaction->isPending();
    }

    public function attachReceipt(User $user, Transaction $transaction): bool
    {
        return $this->context->isAdmin()
            || ($transaction->isPending() && (int) $transaction->created_by === (int) $user->getKey());
    }

    public function viewReceipt(User $user, Transaction $transaction): bool
    {
        return $this->context->isAdmin() || $this->context->owns($transaction->member);
    }
}
