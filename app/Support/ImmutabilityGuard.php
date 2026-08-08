<?php

namespace App\Support;

/**
 * Immutability of posted ledger entries and published policies is enforced on
 * the models themselves, so no controller, command or stray query can bypass
 * it. The domain services that legitimately need to write those rows — posting,
 * reversal, policy publication — lift the guard for the duration of one closure
 * through this class.
 *
 * Keeping the flag here rather than on each model lets related models (a journal
 * entry and its lines) share one scope.
 */
final class ImmutabilityGuard
{
    public const LEDGER = 'ledger';
    public const POLICY = 'policy';

    /** @var array<string, int> */
    private static array $depth = [];

    public static function isLifted(string $scope): bool
    {
        return (self::$depth[$scope] ?? 0) > 0;
    }

    /**
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function without(string $scope, callable $callback): mixed
    {
        self::$depth[$scope] = (self::$depth[$scope] ?? 0) + 1;

        try {
            return $callback();
        } finally {
            self::$depth[$scope]--;
        }
    }
}
