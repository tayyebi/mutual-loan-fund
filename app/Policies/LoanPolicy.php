<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use App\Support\GroupContext;

class LoanPolicy
{
    public function __construct(private readonly GroupContext $context) {}

    public function viewAny(User $user): bool
    {
        return $this->context->has();
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->context->isAdmin() || $this->context->owns($loan->member);
    }

    public function create(User $user): bool
    {
        return $this->context->has();
    }

    /**
     * Nobody approves their own loan, administrator or not.
     */
    public function approve(User $user, Loan $loan): bool
    {
        return $this->context->isAdmin()
            && $loan->status === Loan::STATUS_REQUESTED
            && ! $this->context->owns($loan->member);
    }

    public function disburse(User $user, Loan $loan): bool
    {
        return $this->context->isAdmin() && $loan->status === Loan::STATUS_APPROVED;
    }

    public function repay(User $user, Loan $loan): bool
    {
        return $loan->isOutstanding()
            && ($this->context->isAdmin() || $this->context->owns($loan->member));
    }

    public function cancel(User $user, Loan $loan): bool
    {
        return in_array($loan->status, [Loan::STATUS_REQUESTED, Loan::STATUS_APPROVED], true)
            && ($this->context->isAdmin() || $this->context->owns($loan->member));
    }
}
