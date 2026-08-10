<?php

namespace App\Domain\Access;

use App\Models\User;
use App\Support\GroupContext;
use OutOfBoundsException;

/**
 * The three access levels the application's URL space is partitioned by.
 *
 * Each level owns one URL prefix and one route-name prefix, and each is a
 * complete experience rather than a filtered version of another. The levels are
 * *cumulative*: an actor who holds a higher level also holds every lower one, so
 * a system administrator who is also a member of a fund uses the very same /u
 * surface any other member uses. Holding a level never removes a lower one — a
 * fund administrator is still an investor.
 *
 * House style: no native PHP enums anywhere in this codebase, so this is a plain
 * class of `public const` strings, like Group::STATUS_ACTIVE or AuditAction.
 */
final class AccessLevel
{
    /** Any approved member of a fund — the person whose money this is. */
    public const LEVEL_MEMBER = 'member';

    /** A member with GroupMembership::ROLE_ADMIN in the fund in context. */
    public const LEVEL_FUND_ADMIN = 'fund_admin';

    /** A platform operator: User::SYSTEM_ROLE_SYSTEM_ADMIN. */
    public const LEVEL_SYSTEM_ADMIN = 'system_admin';

    /**
     * Ranking, low to high. Every comparison in the application derives from
     * this one array rather than from hand-written role conditionals.
     *
     * @var array<string, int>
     */
    public const RANK = [
        self::LEVEL_MEMBER => 1,
        self::LEVEL_FUND_ADMIN => 2,
        self::LEVEL_SYSTEM_ADMIN => 3,
    ];

    /**
     * @return list<string> levels ordered low to high
     */
    public static function all(): array
    {
        return array_keys(self::RANK);
    }

    public static function rank(string $level): int
    {
        return self::RANK[$level]
            ?? throw new OutOfBoundsException("Unknown access level '{$level}'.");
    }

    /**
     * Whether holding $held satisfies a requirement for $required.
     */
    public static function covers(string $held, string $required): bool
    {
        return self::rank($held) >= self::rank($required);
    }

    /**
     * Every level the current actor holds, ordered low to high.
     *
     * Membership levels depend on the fund in context, so a user is a fund
     * administrator on /u/alpha and a plain member on /u/beta within the same
     * session. Nothing here consults user input: the membership has already been
     * verified by ResolveGroupContext.
     *
     * @return list<string>
     */
    public static function heldBy(?User $user, ?GroupContext $context): array
    {
        if (! $user instanceof User || ! $user->isActive()) {
            return [];
        }

        $levels = [];

        // The membership levels only exist relative to a fund. Outside one there
        // is no tenant to be a member *of*, and the cross-fund home at / belongs
        // to no surface, so nothing needs them there.
        if ($context?->has()) {
            $levels[] = self::LEVEL_MEMBER;

            if ($context->isAdmin()) {
                $levels[] = self::LEVEL_FUND_ADMIN;
            }
        }

        if ($user->isSystemAdmin()) {
            $levels[] = self::LEVEL_SYSTEM_ADMIN;
        }

        return $levels;
    }
}
