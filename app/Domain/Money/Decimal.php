<?php

namespace App\Domain\Money;

use InvalidArgumentException;
use Stringable;

/**
 * An arbitrary-precision decimal, backed by bcmath and stored as a string.
 *
 * Money never becomes a PHP float anywhere in this application: floats cannot
 * represent 0.1 exactly, and rounding drift in a ledger is unrecoverable.
 * Passing a float in here is rejected rather than silently converted.
 */
final class Decimal implements Stringable
{
    /** Storage scale for money, matching NUMERIC(30,8) in PostgreSQL. */
    public const MONEY_SCALE = 8;

    /** Storage scale for rates, matching NUMERIC(30,12). */
    public const RATE_SCALE = 12;

    /** Working scale for intermediate results, so division does not lose digits. */
    private const WORKING_SCALE = 24;

    private function __construct(
        private readonly string $value,
        private readonly int $scale,
    ) {}

    public static function of(int|string|self $value, int $scale = self::MONEY_SCALE): self
    {
        if ($value instanceof self) {
            return $value->withScale($scale);
        }

        $normalized = self::normalize((string) $value);

        return new self(self::round($normalized, $scale), $scale);
    }

    public static function zero(int $scale = self::MONEY_SCALE): self
    {
        return new self(self::round('0', $scale), $scale);
    }

    /**
     * Parse a value typed by a human: strips thousands separators and accepts a
     * blank field as "not provided".
     */
    public static function parse(?string $input, int $scale = self::MONEY_SCALE): ?self
    {
        if ($input === null) {
            return null;
        }

        $cleaned = str_replace([',', ' ', "\u{200f}", "\u{a0}"], '', trim($input));

        if ($cleaned === '') {
            return null;
        }

        if (! self::isValid($cleaned)) {
            throw new InvalidArgumentException("'{$input}' is not a valid decimal number.");
        }

        return self::of($cleaned, $scale);
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^-?\d+(\.\d+)?$/', trim($value));
    }

    public function plus(int|string|self $other): self
    {
        return new self(
            self::round(bcadd($this->value, self::raw($other), self::WORKING_SCALE), $this->scale),
            $this->scale
        );
    }

    public function minus(int|string|self $other): self
    {
        return new self(
            self::round(bcsub($this->value, self::raw($other), self::WORKING_SCALE), $this->scale),
            $this->scale
        );
    }

    public function times(int|string|self $other): self
    {
        return new self(
            self::round(bcmul($this->value, self::raw($other), self::WORKING_SCALE), $this->scale),
            $this->scale
        );
    }

    public function dividedBy(int|string|self $divisor, ?int $scale = null): self
    {
        $raw = self::raw($divisor);

        if (bccomp($raw, '0', self::WORKING_SCALE) === 0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        $scale ??= $this->scale;

        return new self(
            self::round(bcdiv($this->value, $raw, self::WORKING_SCALE), $scale),
            $scale
        );
    }

    /**
     * Percentage of this value, e.g. rate 5 means 5%.
     */
    public function percent(int|string|self $rate): self
    {
        return $this->times(self::raw($rate))->dividedBy('100');
    }

    public function negated(): self
    {
        return $this->times('-1');
    }

    public function abs(): self
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    public function compareTo(int|string|self $other): int
    {
        return bccomp($this->value, self::raw($other), self::WORKING_SCALE);
    }

    public function equals(int|string|self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function greaterThan(int|string|self $other): bool
    {
        return $this->compareTo($other) === 1;
    }

    public function greaterThanOrEqual(int|string|self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    public function lessThan(int|string|self $other): bool
    {
        return $this->compareTo($other) === -1;
    }

    public function lessThanOrEqual(int|string|self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    public function isZero(): bool
    {
        return $this->compareTo('0') === 0;
    }

    public function isPositive(): bool
    {
        return $this->compareTo('0') === 1;
    }

    public function isNegative(): bool
    {
        return $this->compareTo('0') === -1;
    }

    public function min(self $other): self
    {
        return $this->lessThanOrEqual($other) ? $this : $other;
    }

    public function max(self $other): self
    {
        return $this->greaterThanOrEqual($other) ? $this : $other;
    }

    public function withScale(int $scale): self
    {
        return $scale === $this->scale
            ? $this
            : new self(self::round($this->value, $scale), $scale);
    }

    public function scale(): int
    {
        return $this->scale;
    }

    /**
     * The canonical string form, suitable for a NUMERIC column.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Human-readable form: trailing zeros trimmed to at most $maxDecimals, with
     * thousands separators. Display only — never feed this back into arithmetic.
     */
    public function format(int $maxDecimals = 2, bool $trimZeros = true): string
    {
        $rounded = self::round($this->value, $maxDecimals);
        $negative = str_starts_with($rounded, '-');
        $abs = ltrim($rounded, '-');

        [$whole, $fraction] = array_pad(explode('.', $abs, 2), 2, '');

        if ($trimZeros) {
            $fraction = rtrim($fraction, '0');
        }

        $grouped = strrev(implode(',', str_split(strrev($whole), 3)));
        $result = $fraction === '' ? $grouped : $grouped.'.'.$fraction;

        return ($negative && $result !== '0' ? '-' : '').$result;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function raw(int|string|self $value): string
    {
        if ($value instanceof self) {
            return $value->value;
        }

        return self::normalize((string) $value);
    }

    private static function normalize(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '' || $trimmed === '-') {
            throw new InvalidArgumentException('An empty string is not a decimal number.');
        }

        if (! self::isValid($trimmed)) {
            throw new InvalidArgumentException("'{$value}' is not a valid decimal number.");
        }

        return $trimmed;
    }

    /**
     * Half-up rounding. bcmath truncates, which would systematically bias every
     * conversion towards zero.
     */
    private static function round(string $value, int $scale): string
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale cannot be negative.');
        }

        $negative = str_starts_with($value, '-');
        $abs = ltrim($value, '-');

        $half = '0.'.str_repeat('0', $scale).'5';
        $rounded = bcadd($abs, $half, $scale);

        if ($negative && bccomp($rounded, '0', $scale) !== 0) {
            return '-'.$rounded;
        }

        return $rounded;
    }
}
