<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\EntryDraft;
use App\Domain\Accounting\Exceptions\PostingException;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * The fund pays a loan out to a member.
 *
 *   Debit  Loans Receivable  (attributed to the member — the fund now owns a claim)
 *   Credit treasury          (the asset leaves)
 */
class LoanDisbursementTemplate extends BaseTemplate
{
    public function key(): string
    {
        return 'LoanDisbursement';
    }

    public function build(Transaction $transaction): EntryDraft
    {
        $loan = $transaction->loan ?? throw new PostingException(
            "Transaction #{$transaction->getKey()} is a disbursement with no loan attached."
        );

        $treasury = $this->treasury($transaction);
        $amount = $this->money($transaction);
        $member = $transaction->member?->displayName() ?? 'member';

        $draft = EntryDraft::make(
            $this->key(),
            Carbon::parse($transaction->occurred_on),
            $this->describe($transaction, "Loan {$loan->reference} disbursed to {$member}")
        )
            ->forTransaction($transaction)
            ->debit(
                $this->chart->require($transaction->group, Account::LOANS_RECEIVABLE),
                $amount,
                $loan->costCenter,
                "Loan {$loan->reference}"
            )
            ->credit($this->treasuryAccount($treasury), $amount, null, $treasury->name);

        return $this->addFee($draft, $transaction, $treasury);
    }
}
