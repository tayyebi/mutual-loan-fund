<?php

namespace App\Domain\Money;

use InvalidArgumentException;
use Stringable;

/**
 * A Decimal with a currency attached.
 *
 * Its whole purpose is to make "10,000 USDT + 920,000,000 IRT" impossible to
 * write by accident: cross-currency arithmetic has to go through the exchange
 * rate layer explicitly.
 */
final class Money implements Stringable
{
    private function __construct(
        public readonly Decimal $amount,
        public readonly string $currency,
    ) {}

    public static function of(int|string|float|Decimal $amount, string $currency): self
    {
        // Decimal::of is what refuses a float; widening the union here just
        // stops PHP coercing one to int before it gets there.
        return new self(Decimal::of($amount), self::normalizeCurrency($currency));
    }

    public static function zero(string $currency): self
    {
        return new self(Decimal::zero(), self::normalizeCurrency($currency));
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount->plus($other->amount), $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount->minus($other->amount), $this->currency);
    }

    public function times(int|string|float|Decimal $factor): self
    {
        return new self($this->amount->times($factor), $this->currency);
    }

    public function dividedBy(int|string|float|Decimal $divisor): self
    {
        return new self($this->amount->dividedBy($divisor), $this->currency);
    }

    public function negated(): self
    {
        return new self($this->amount->negated(), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function isPositive(): bool
    {
        return $this->amount->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->amount->compareTo($other->amount);
    }

    public function withAmount(Decimal $amount): self
    {
        return new self($amount, $this->currency);
    }

    public function isSameCurrency(self $other): bool
    {
        return $this->currency === $other->currency;
    }

    /**
     * Display form using the currency's conventional number of decimals.
     */
    public function format(bool $withCode = true): string
    {
        $decimals = (int) config("fund.currencies.{$this->currency}.scale", 2);
        $formatted = $this->amount->format($decimals, $decimals > 2);

        return $withCode ? $formatted.' '.$this->currency : $formatted;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if (! $this->isSameCurrency($other)) {
            throw new InvalidArgumentException(
                "Cannot combine {$this->currency} with {$other->currency}: convert through the exchange rate layer first."
            );
        }
    }

    private static function normalizeCurrency(string $currency): string
    {
        $normalized = strtoupper(trim($currency));

        if ($normalized === '') {
            throw new InvalidArgumentException('A currency code is required.');
        }

        return $normalized;
    }
}
