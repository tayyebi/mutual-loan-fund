<?php

namespace App\Domain\Policies;

/**
 * Field-by-field comparison of two policy versions.
 *
 * This is administrative convenience for the compare and publish-confirmation
 * screens. It is not an accounting operation and never touches the ledger.
 */
class PolicyDiff
{
    /**
     * @return list<array{category: string, field: string, from: string, to: string}>
     */
    public function changes(PolicyConfig $from, PolicyConfig $to): array
    {
        $changes = [];

        foreach ($to->categories() as $key => $category) {
            $previous = $from->category($key);

            foreach ($category::fields() as $field) {
                $before = $previous->get($field->key);
                $after = $category->get($field->key);

                if ($this->same($before, $after)) {
                    continue;
                }

                $changes[] = [
                    'category' => $category::label(),
                    'field' => $field->label,
                    'from' => $field->display($before),
                    'to' => $field->display($after),
                ];
            }
        }

        return $changes;
    }

    public function hasChanges(PolicyConfig $from, PolicyConfig $to): bool
    {
        return $this->changes($from, $to) !== [];
    }

    private function same(mixed $before, mixed $after): bool
    {
        if ($before === null || $after === null) {
            return $before === $after;
        }

        if (is_bool($before) || is_bool($after)) {
            return (bool) $before === (bool) $after;
        }

        // Decimal strings compare by value, so "0" and "0.00000000" are equal.
        if (is_numeric($before) && is_numeric($after)) {
            return bccomp((string) $before, (string) $after, 12) === 0;
        }

        return (string) $before === (string) $after;
    }
}
