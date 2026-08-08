<?php

namespace App\Domain\Accounting\Templates;

use App\Domain\Accounting\ChartOfAccounts;
use App\Domain\Accounting\EntryDraft;
use App\Domain\Accounting\Exceptions\PostingException;
use App\Domain\Accounting\FunctionalCurrencyResolver;
use App\Domain\ExchangeRates\RateService;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * One asset is exchanged for another: 1,000 USDT out, 920,000,000 IRT in.
 *
 *   Credit source treasury       (native amount that left)
 *   Debit  destination treasury  (native amount that arrived)
 *   Debit/Credit Exchange Loss/Gain for the difference
 *
 * The two legs are valued at the day's reference rates. The execution rate the
 * fund actually got almost never equals the reference rate, and that difference
 * is a real gain or loss — recognising it explicitly is what keeps the entry
 * balanced without silently adjusting either treasury's native amount.
 */
class TreasuryExchangeTemplate extends BaseTemplate
{
    public function __construct(
        ChartOfAccounts $chart,
        private readonly RateService $rates,
        private readonly FunctionalCurrencyResolver $functionalCurrency,
    ) {
        parent::__construct($chart);
    }

    public function key(): string
    {
        return 'TreasuryExchange';
    }

    public function build(Transaction $transaction): EntryDraft
    {
        $source = $this->treasury($transaction);
        $destination = $transaction->counterTreasury ?? throw new PostingException(
            "Exchange #{$transaction->getKey()} has no destination treasury."
        );

        if ($transaction->counter_amount === null || $transaction->counter_currency === null) {
            throw new PostingException("Exchange #{$transaction->getKey()} is missing its destination amount.");
        }

        $date = Carbon::parse($transaction->occurred_on);
        $sourceAmount = $this->money($transaction);
        $destinationAmount = Money::of((string) $transaction->counter_amount, $transaction->counter_currency);

        $draft = EntryDraft::make(
            $this->key(),
            $date,
            $this->describe(
                $transaction,
                "Exchange {$sourceAmount->format()} from {$source->name} to {$destinationAmount->format()} in {$destination->name}"
            )
        )
            ->forTransaction($transaction)
            ->debit($this->treasuryAccount($destination), $destinationAmount, null, $destination->name)
            ->credit($this->treasuryAccount($source), $sourceAmount, null, $source->name);

        $draft = $this->addFee($draft, $transaction, $source);

        return $this->recogniseDifference($draft, $transaction, $date, $sourceAmount, $destinationAmount);
    }

    /**
     * Value both legs in the functional currency and book the difference.
     *
     * The fee lines are ignored here: a fee contributes an equal debit and
     * credit, so it cannot affect the balance.
     */
    private function recogniseDifference(
        EntryDraft $draft,
        Transaction $transaction,
        Carbon $date,
        Money $sourceAmount,
        Money $destinationAmount,
    ): EntryDraft {
        $functional = $this->functionalCurrency->forTransaction($transaction);

        $valuedOut = $this->toFunctional($sourceAmount, $functional, $date);
        $valuedIn = $this->toFunctional($destinationAmount, $functional, $date);

        $difference = $valuedIn->minus($valuedOut);

        if ($difference->isZero()) {
            return $draft;
        }

        $group = $transaction->group;

        // More value arrived than left: a gain. Less: a loss.
        return $difference->isPositive()
            ? $draft->credit(
                $this->chart->require($group, Account::EXCHANGE_GAIN),
                Money::of($difference, $functional),
                null,
                'Exchange gain'
            )
            : $draft->debit(
                $this->chart->require($group, Account::EXCHANGE_LOSS),
                Money::of($difference->abs(), $functional),
                null,
                'Exchange loss'
            );
    }

    private function toFunctional(Money $amount, string $functional, Carbon $date): Decimal
    {
        return $amount->currency === $functional
            ? $amount->amount
            : $this->rates->convert($amount->amount, $amount->currency, $functional, $date);
    }
}
