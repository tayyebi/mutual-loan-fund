<?php

namespace App\Domain\Access;

use App\Domain\Access\Surfaces\MemberSurface;
use OutOfBoundsException;

/**
 * Resolves an *intent* to the route that serves it on the current surface.
 *
 * A handful of views are rendered by both /u and /g — a transaction looks the
 * same whether a member is checking their own payment or an administrator is
 * verifying it. Those views cannot hardcode a route name: 'g.transactions.show'
 * would send a member into a 403, and 'u.activity.show' would strand an
 * administrator outside their console.
 *
 * So they ask for what they mean — "show this transaction" — and each Surface
 * declares which of its own routes answers that. Adding a shared view means
 * adding one intent to each surface's routes(); a surface that does not serve
 * an intent simply omits it, and asking for it there is a loud error rather
 * than a silent link to the wrong place.
 *
 * In Blade this is the @surface directive:
 *
 *     <a href="@surface('transaction.show', $group, $transaction)">…</a>
 */
final class SurfaceRoute
{
    /**
     * The route name serving $intent on the current surface.
     */
    public static function name(string $intent): string
    {
        $surface = AccessMap::current() ?? MemberSurface::class;

        $route = $surface::routes()[$intent] ?? null;

        if ($route === null) {
            throw new OutOfBoundsException(
                "Surface '{$surface::key()}' declares no route for the intent '{$intent}'."
            );
        }

        return $route;
    }

    public static function to(string $intent, mixed ...$parameters): string
    {
        return route(self::name($intent), $parameters);
    }

    /**
     * Whether the current surface serves this intent at all — for views that
     * offer an action only where it exists, such as cancelling a loan, which is
     * the borrower's move and has no counterpart on the administrator's side.
     */
    public static function serves(string $intent): bool
    {
        $surface = AccessMap::current() ?? MemberSurface::class;

        return isset($surface::routes()[$intent]);
    }
}
