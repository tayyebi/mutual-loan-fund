<?php

namespace App\Domain\Policies;

use App\Domain\Money\Decimal;

/**
 * Describes one configurable policy value.
 *
 * The field list is the single source of truth for a category: hydration,
 * serialisation, the edit form, validation and the version diff are all driven
 * from it, so adding a new policy value means adding one field here and nothing
 * else.
 */
final class PolicyField
{
    public const TYPE_BOOL = 'bool';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_ENUM = 'enum';
    public const TYPE_STRING = 'string';

    /**
     * @param  array<string, string>  $options  value => label, for enum fields
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly mixed $default,
        public readonly ?string $suffix = null,
        public readonly array $options = [],
        public readonly bool $nullable = false,
        public readonly ?string $help = null,
        public readonly bool $monetary = false,
    ) {}

    public static function bool(string $key, string $label, bool $default, ?string $help = null): self
    {
        return new self($key, $label, self::TYPE_BOOL, $default, help: $help);
    }

    public static function money(string $key, string $label, ?string $default, bool $nullable = false, ?string $help = null): self
    {
        return new self($key, $label, self::TYPE_DECIMAL, $default, nullable: $nullable, help: $help, monetary: true);
    }

    public static function rate(string $key, string $label, string $default, ?string $help = null): self
    {
        return new self($key, $label, self::TYPE_DECIMAL, $default, suffix: '%', help: $help);
    }

    public static function integer(string $key, string $label, ?int $default, ?string $suffix = null, bool $nullable = false, ?string $help = null): self
    {
        return new self($key, $label, self::TYPE_INTEGER, $default, suffix: $suffix, nullable: $nullable, help: $help);
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function enum(string $key, string $label, array $options, string $default, ?string $help = null): self
    {
        return new self($key, $label, self::TYPE_ENUM, $default, options: $options, help: $help);
    }

    /**
     * Coerce a stored or submitted value into its canonical in-memory form.
     */
    public function cast(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $this->nullable ? null : $this->default;
        }

        return match ($this->type) {
            self::TYPE_BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $this->default,
            self::TYPE_INTEGER => (int) $value,
            // Monetary and rate values stay decimal strings, never floats.
            self::TYPE_DECIMAL => Decimal::isValid((string) $value)
                ? Decimal::of((string) $value)->toString()
                : (string) $value,
            default => (string) $value,
        };
    }

    public function isDecimal(): bool
    {
        return $this->type === self::TYPE_DECIMAL;
    }

    /**
     * Render a value for the policy history and comparison screens.
     */
    public function display(mixed $value): string
    {
        if ($value === null) {
            return 'unlimited';
        }

        return match ($this->type) {
            self::TYPE_BOOL => $value ? 'Yes' : 'No',
            self::TYPE_DECIMAL => Decimal::isValid((string) $value)
                ? Decimal::of((string) $value)->format($this->monetary ? 2 : 4).($this->suffix ? $this->suffix : '')
                : (string) $value,
            self::TYPE_INTEGER => $value.($this->suffix ? ' '.$this->suffix : ''),
            self::TYPE_ENUM => $this->options[$value] ?? (string) $value,
            default => (string) $value,
        };
    }
}
