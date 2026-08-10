<?php

namespace App\Domain\ExchangeRates;

use App\Domain\Audit\AuditAction;
use App\Domain\Audit\AuditRecorder;
use App\Domain\ExchangeRates\Exceptions\MissingRateException;
use App\Domain\Money\Decimal;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Global valuation data, quoted against one gram of 18K gold.
 *
 * Rates are application-wide, never group-scoped, and are entered by hand: V1
 * has no market feed. Resolution falls back to the most recent earlier rate and
 * says so, and never fabricates a value.
 */
class RateService
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function goldUnit(): string
    {
        return (string) config('fund.gold_unit');
    }

    /**
     * @return list<string> units that can carry a rate
     */
    public function quotableUnits(): array
    {
        return array_values(array_filter(
            array_keys((array) config('fund.currencies', [])),
            fn (string $unit) => $unit !== $this->goldUnit()
        ));
    }

    /**
     * The effective rate for a unit on a date: the latest rate whose
     * effective_date is on or before that date.
     */
    public function quote(string $unit, ?Carbon $date = null): RateQuote
    {
        $unit = strtoupper($unit);
        $date = ($date ?? Carbon::today())->copy()->startOfDay();

        if ($unit === $this->goldUnit()) {
            return RateQuote::reference($unit, $date);
        }

        $rate = ExchangeRate::query()
            ->where('unit', $unit)
            ->whereDate('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if (! $rate) {
            throw new MissingRateException($unit, $date);
        }

        return new RateQuote(
            $unit,
            Decimal::of((string) $rate->units_per_gram_18k, Decimal::RATE_SCALE),
            Carbon::parse($rate->effective_date),
            $date
        );
    }

    public function tryQuote(string $unit, ?Carbon $date = null): ?RateQuote
    {
        try {
            return $this->quote($unit, $date);
        } catch (MissingRateException) {
            return null;
        }
    }

    /**
     * Value an amount in grams of 18K gold.
     */
    public function toGold(Decimal $amount, string $unit, ?Carbon $date = null): Decimal
    {
        return $this->quote($unit, $date)->toGold($amount);
    }

    /**
     * Convert between two units through the gold reference.
     *
     * Both legs are quoted for the same date, so a conversion is always internally
     * consistent even when the two units' rates were entered on different days.
     */
    public function convert(Decimal $amount, string $from, string $to, ?Carbon $date = null): Decimal
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        // Through the gold reference in one step: converting via an already
        // rounded gram amount would carry a double rounding error into the
        // result.
        $fromQuote = $this->quote($from, $date);
        $toQuote = $this->quote($to, $date);

        return $amount
            ->times($toQuote->unitsPerGram18k)
            ->dividedBy($fromQuote->unitsPerGram18k, Decimal::MONEY_SCALE);
    }

    /**
     * The rate used to express one unit of $from in $to, snapshotted onto
     * journal lines so historical valuations survive later rate changes.
     */
    public function crossRate(string $from, string $to, ?Carbon $date = null): Decimal
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return Decimal::of('1', Decimal::RATE_SCALE);
        }

        return $this->convert(Decimal::of('1', Decimal::RATE_SCALE), $from, $to, $date)
            ->withScale(Decimal::RATE_SCALE);
    }

    /**
     * Record a manually entered rate.
     *
     * A direct 18K gram rate is authoritative. When only the standard market
     * quote (1 troy oz of 24K) is supplied it is converted mathematically, and
     * the original input is kept for reference.
     */
    public function record(
        string $unit,
        ?Decimal $unitsPerGram18k,
        Carbon $effectiveDate,
        User $actor,
        ?Decimal $troyOunce24k = null,
        ?string $note = null,
    ): ExchangeRate {
        $unit = strtoupper($unit);

        if ($unit === $this->goldUnit()) {
            throw new InvalidArgumentException(__('exceptions.gold_unit_cannot_be_quoted'));
        }

        if (! in_array($unit, $this->quotableUnits(), true)) {
            throw new InvalidArgumentException(__('exceptions.valuation_unit_unsupported', ['unit' => $unit]));
        }

        if ($unitsPerGram18k === null && $troyOunce24k !== null) {
            $unitsPerGram18k = GoldConverter::troyOunce24kToGram18k($troyOunce24k);
        }

        if ($unitsPerGram18k === null || ! $unitsPerGram18k->isPositive()) {
            throw new InvalidArgumentException(__('exceptions.rate_must_be_positive'));
        }

        $rate = ExchangeRate::updateOrCreate(
            ['unit' => $unit, 'effective_date' => $effectiveDate->toDateString()],
            [
                'units_per_gram_18k' => $unitsPerGram18k->withScale(Decimal::RATE_SCALE)->toString(),
                'source_troy_ounce_24k' => $troyOunce24k?->withScale(Decimal::RATE_SCALE)->toString(),
                'source_note' => $note,
                'created_by' => $actor->getKey(),
            ]
        );

        $this->audit->record(
            AuditAction::EXCHANGE_RATE_ENTERED,
            actor: $actor,
            object: $rate,
            new: [
                'unit' => $unit,
                'units_per_gram_18k' => (string) $rate->units_per_gram_18k,
                'effective_date' => $effectiveDate->toDateString(),
            ]
        );

        return $rate;
    }

    /**
     * Latest quote per unit, for the exchange rate screen.
     *
     * @return Collection<string, RateQuote>
     */
    public function currentQuotes(?Carbon $date = null): Collection
    {
        $date ??= Carbon::today();

        return collect($this->quotableUnits())
            ->mapWithKeys(fn (string $unit) => [$unit => $this->tryQuote($unit, $date)])
            ->filter();
    }
}
