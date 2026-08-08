<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\ChartOfAccounts;
use App\Domain\Accounting\EntryDraft;
use App\Domain\Accounting\Exceptions\PostingException;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Transaction;
use App\Models\Treasury;

abstract class BaseTemplate implements TransactionTemplate
{
    public function __construct(protected readonly ChartOfAccounts $chart) {}

    protected function treasury(Transaction $transaction): Treasury
    {
        return $transaction->treasury ?? throw new PostingException(
            "Transaction #{$transaction->getKey()} has no treasury; there is nowhere for the money to be."
        );
    }

    protected function treasuryAccount(Treasury $treasury): Account
    {
        return $treasury->account ?? throw new PostingException(
            "Treasury '{$treasury->name}' has no ledger account."
        );
    }

    /**
     * The member's cost center: who the activity belongs to.
     */
    protected function memberCostCenter(Transaction $transaction): CostCenter
    {
        $costCenter = $transaction->member?->costCenter;

        return $costCenter ?? throw new PostingException(
            "Transaction #{$transaction->getKey()} needs a member cost center for attribution."
        );
    }

    protected function money(Transaction $transaction): Money
    {
        return Money::of((string) $transaction->amount, $transaction->currency);
    }

    /**
     * A fee borne by the fund: an expense funded from the treasury that paid it.
     *
     * Fees are separate lines rather than netted off the amount, so the treasury
     * movement in the ledger matches the movement on the blockchain or the bank
     * statement.
     */
    protected function addFee(EntryDraft $draft, Transaction $transaction, Treasury $treasury): EntryDraft
    {
        $fee = $transaction->fee_amount === null
            ? Decimal::zero()
            : Decimal::of((string) $transaction->fee_amount);

        if (! $fee->isPositive()) {
            return $draft;
        }

        $currency = $transaction->fee_currency ?? $treasury->currency;

        if ($currency !== $treasury->currency) {
            throw new PostingException(
                "Fee currency {$currency} does not match treasury currency {$treasury->currency}."
            );
        }

        $amount = Money::of($fee, $currency);

        return $draft
            ->debit($this->chart->feeAccountFor($treasury), $amount, null, 'Transaction fee')
            ->credit($this->treasuryAccount($treasury), $amount, null, 'Transaction fee');
    }

    protected function describe(Transaction $transaction, string $fallback): string
    {
        return filled($transaction->description) ? $transaction->description : $fallback;
    }
}
