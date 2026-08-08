<?php

namespace App\Domain\Accounting;

use App\Models\Group;
use App\Models\Transaction;

/**
 * Which currency an entry balances in.
 *
 * For a transaction it is the functional currency recorded in the policy version
 * the transaction was created under — not today's policy. A later change to the
 * group's functional currency must not re-denominate entries already posted.
 */
class FunctionalCurrencyResolver
{
    public function forGroup(Group $group): string
    {
        return $group->functionalCurrency();
    }

    public function forTransaction(Transaction $transaction): string
    {
        $fromPolicy = $transaction->policyVersion?->config('accounting.functional_currency');

        return $fromPolicy ?: $this->forGroup($transaction->group);
    }
}
