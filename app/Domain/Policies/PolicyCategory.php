<?php

namespace App\Domain\Policies;

use App\Domain\Money\Decimal;
use OutOfBoundsException;

/**
 * Base class for a group of related policy values (loans, contributions, ...).
 *
 * Subclasses declare a field list and nothing more; hydration, serialisation and
 * rendering are generic, which is what lets new policy categories be added later
 * without touching the publisher, validator or UI.
 */
abstract class PolicyCategory
{
    /**
     * @param  array<string, mixed>  $values
     */
    final public function __construct(protected readonly array $values) {}

    abstract public static function key(): string;

    abstract public static function label(): string;

    /**
     * @return array<string, PolicyField>
     */
    abstract public static function fields(): array;

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (static::fields() as $field) {
            $defaults[$field->key] = $field->default;
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $values = [];

        foreach (static::fields() as $field) {
            $values[$field->key] = $field->cast($data[$field->key] ?? $field->default);
        }

        return new static($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Reading a policy value as a Decimal, e.g. maximum loan amount.
     */
    public function decimal(string $key): ?Decimal
    {
        $value = $this->get($key);

        return $value === null ? null : Decimal::of((string) $value);
    }

    public function integer(string $key): ?int
    {
        $value = $this->get($key);

        return $value === null ? null : (int) $value;
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key, false);
    }

    /**
     * Whether the whole category is switched on. Categories without an 'enabled'
     * field are always on.
     */
    public function enabled(): bool
    {
        return ! array_key_exists('enabled', $this->values) || (bool) $this->values['enabled'];
    }

    public static function field(string $key): PolicyField
    {
        $fields = static::fields();

        if (! isset($fields[$key])) {
            throw new OutOfBoundsException(static::key().' has no policy field '.$key);
        }

        return $fields[$key];
    }

    /**
     * Supports the documented reading style: $policy->loans->maximum_amount
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }
}
