<?php

namespace App\Domain\ExchangeRates;

use App\Domain\Money\Decimal;

/**
 * Fixed mathematical conversions around the application's reference asset,
 * one gram of 18K gold.
 *
 * These are definitions, not market data: they are never entered by hand and
 * never stored as exchange rates.
 */
final class GoldConverter
{
    /** 18K gold is 75% pure. */
    public const PURITY_18K = '0.75';

    /** Grams in one troy ounce. */
    public const GRAMS_PER_TROY_OUNCE = '31.1034768';

    /**
     * Convert the standard market quote (1 troy oz of 24K in some currency) to
     * the application's unit (1 gram of 18K in that same currency).
     *
     *   1 troy oz 24K = 31.1034768 g pure gold = 41.4713024 g of 18K equivalent
     */
    public static function troyOunce24kToGram18k(Decimal $pricePerTroyOunce): Decimal
    {
        return $pricePerTroyOunce
            ->dividedBy(self::GRAMS_PER_TROY_OUNCE, Decimal::RATE_SCALE)
            ->times(self::PURITY_18K)
            ->withScale(Decimal::RATE_SCALE);
    }

    /**
     * The inverse, for showing an entered 18K gram rate as a market quote.
     */
    public static function gram18kToTroyOunce24k(Decimal $pricePerGram18k): Decimal
    {
        return $pricePerGram18k
            ->dividedBy(self::PURITY_18K, Decimal::RATE_SCALE)
            ->times(self::GRAMS_PER_TROY_OUNCE)
            ->withScale(Decimal::RATE_SCALE);
    }

    /**
     * Grams of 18K equivalent in one troy ounce of 24K: 41.4713024.
     */
    public static function gram18kPerTroyOunce24k(): Decimal
    {
        return Decimal::of(self::GRAMS_PER_TROY_OUNCE, Decimal::RATE_SCALE)
            ->dividedBy(self::PURITY_18K, Decimal::RATE_SCALE);
    }

    /**
     * Grams of pure gold in a quantity of 18K gold.
     */
    public static function gram18kToPureGrams(Decimal $grams18k): Decimal
    {
        return $grams18k->times(self::PURITY_18K);
    }
}
