<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\EntryDraft;
use App\Domain\Accounting\Exceptions\PostingException;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * Money moves between two of the fund's own treasuries, in one currency.
 *
 *   Debit  destination treasury
 *   Credit source treasury
 *
 * Nothing enters or leaves the fund, so no income, expense or equity account is
 * touched.
 */
class TreasuryTransferTemplate extends BaseTemplate
{
    public function key(): string
    {
        return 'TreasuryTransfer';
    }

    public function build(Transaction $transaction): EntryDraft
    {
        $source = $this->treasury($transaction);
        $destination = $transaction->counterTreasury ?? throw new PostingException(
            __('exceptions.transfer_no_destination_treasury', ['id' => $transaction->getKey()])
        );

        if ($source->currency !== $destination->currency) {
            throw new PostingException(
                __('exceptions.transfer_currency_mismatch')
            );
        }

        $amount = $this->money($transaction);

        $draft = EntryDraft::make(
            $this->key(),
            Carbon::parse($transaction->occurred_on),
            $this->describe($transaction, "Transfer from {$source->name} to {$destination->name}")
        )
            ->forTransaction($transaction)
            ->debit($this->treasuryAccount($destination), $amount, null, $destination->name)
            ->credit($this->treasuryAccount($source), $amount, null, $source->name);

        return $this->addFee($draft, $transaction, $source);
    }
}
