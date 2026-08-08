<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\EntryDraft;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * A standalone cost paid out of a treasury — a network fee, a bank charge.
 *
 *   Debit  Network Fees / Bank Fees
 *   Credit treasury
 */
class FeeTemplate extends BaseTemplate
{
    public function key(): string
    {
        return 'Fee';
    }

    public function build(Transaction $transaction): EntryDraft
    {
        $treasury = $this->treasury($transaction);
        $amount = $this->money($transaction);

        return EntryDraft::make(
            $this->key(),
            Carbon::parse($transaction->occurred_on),
            $this->describe($transaction, "Fee paid from {$treasury->name}")
        )
            ->forTransaction($transaction)
            ->debit($this->chart->feeAccountFor($treasury), $amount, null, 'Fee')
            ->credit($this->treasuryAccount($treasury), $amount, null, $treasury->name);
    }
}
