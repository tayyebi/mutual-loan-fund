<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\EntryDraft;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * A member pays money into the fund.
 *
 *   Debit  treasury        (the asset arrives)
 *   Credit Fund Capital    (attributed to the member's cost center)
 *
 * The equity/liability classification of member money follows the fund's
 * accounting policy; the account is configurable, the attribution is not.
 */
class ContributionTemplate extends BaseTemplate
{
    public function key(): string
    {
        return 'Contribution';
    }

    public function build(Transaction $transaction): EntryDraft
    {
        $treasury = $this->treasury($transaction);
        $costCenter = $this->memberCostCenter($transaction);
        $amount = $this->money($transaction);
        $member = $transaction->member?->displayName() ?? 'member';

        $draft = EntryDraft::make(
            $this->key(),
            Carbon::parse($transaction->occurred_on),
            $this->describe($transaction, "Contribution from {$member}")
        )
            ->forTransaction($transaction)
            ->debit($this->treasuryAccount($treasury), $amount, null, $treasury->name)
            ->credit(
                $this->chart->require($transaction->group, Account::FUND_CAPITAL),
                $amount,
                $costCenter,
                "Contribution from {$member}"
            );

        return $this->addFee($draft, $transaction, $treasury);
    }
}
