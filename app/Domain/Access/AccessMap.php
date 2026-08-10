<?php

namespace App\Domain\Access;

use App\Domain\Access\Surfaces\FundAdminSurface;
use App\Domain\Access\Surfaces\MemberSurface;
use App\Domain\Access\Surfaces\SystemSurface;
use OutOfBoundsException;

/**
 * The registry of surfaces — the one place the access-level → UX mapping lives.
 *
 * Mirrors PolicyConfig::CATEGORIES: a single declarative list that everything
 * downstream is generic over. Additional surfaces can be added to SURFACES
 * later; the layout, the switcher and the navigation builder need no change.
 */
final class AccessMap
{
    /** @var list<class-string<Surface>> ordered low to high */
    public const SURFACES = [
        MemberSurface::class,
        FundAdminSurface::class,
        SystemSurface::class,
    ];

    /**
     * @return class-string<Surface>
     */
    public static function surface(string $key): string
    {
        foreach (self::SURFACES as $surface) {
            if ($surface::key() === $key) {
                return $surface;
            }
        }

        throw new OutOfBoundsException("Unknown surface '{$key}'.");
    }

    /**
     * The surface serving the current request, decided from its route name.
     *
     * @return class-string<Surface>|null null on pages that belong to no surface
     *                                    (login, the cross-fund home, /p)
     */
    public static function current(): ?string
    {
        foreach (self::SURFACES as $surface) {
            if ($surface::isCurrent()) {
                return $surface;
            }
        }

        return null;
    }

    /**
     * The surfaces an actor holding these levels may enter, ordered low to high.
     *
     * @param  list<string>  $levels
     * @return list<class-string<Surface>>
     */
    public static function availableTo(array $levels): array
    {
        return array_values(array_filter(
            self::SURFACES,
            fn (string $surface) => in_array($surface::level(), $levels, true),
        ));
    }
}
