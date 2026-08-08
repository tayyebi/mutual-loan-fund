<?php

namespace App\Domain\ExchangeRates;

use App\Domain\Money\Decimal;
use Illuminate\Support\Carbon;

/**
 * A rate resolved for a specific date: how many units of $unit equal one gram
 * of 18K gold.
 *
 * A quote knows whether it is a fallback — the latest rate on or before the
 * requested date rather than one entered for that day — so the UI can say so
 * instead of presenting stale data as current.
 */
final class RateQuote
{
    public function __construct(
        public readonly string $unit,
        public readonly Decimal $unitsPerGram18k,
        public readonly Carbon $effectiveDate,
        public readonly Carbon $requestedDate,
        public readonly bool $isReference = false,
    ) {}

    /**
     * The gold unit itself: one gram of 18K gold is one gram of 18K gold.
     */
    public static function reference(string $unit, Carbon $date): self
    {
        return new self($unit, Decimal::of('1', Decimal::RATE_SCALE), $date, $date, true);
    }

    /**
     * True when no rate was entered for the requested date and an earlier one
     * was carried forward.
     */
    public function isFallback(): bool
    {
        return ! $this->isReference && ! $this->effectiveDate->isSameDay($this->requestedDate);
    }

    public function ageInDays(): int
    {
        return (int) $this->effectiveDate->diffInDays($this->requestedDate);
    }

    /**
     * Value of an amount in this unit, expressed in grams of 18K gold.
     */
    public function toGold(Decimal $amount): Decimal
    {
        return $amount->dividedBy($this->unitsPerGram18k, Decimal::MONEY_SCALE);
    }

    /**
     * Value of a quantity of 18K gold, expressed in this unit.
     */
    public function fromGold(Decimal $grams): Decimal
    {
        return $grams->times($this->unitsPerGram18k)->withScale(Decimal::MONEY_SCALE);
    }
}
