<?php

namespace App\Domain\Access;

/**
 * One access level's complete experience.
 *
 * A surface owns a URL prefix, a route-name prefix and a navigation structure.
 * Declaring a surface is the *only* thing needed to give an access level its own
 * UX: the layout, the surface switcher and the active-state highlighting are all
 * generic and driven from the declarations below, exactly as the policy edit
 * form is driven from PolicyCategory::fields().
 *
 * Adding a destination to a surface means adding one NavItem to one section.
 */
abstract class Surface
{
    /** Stable identifier, also the URL prefix: 'u', 'g', 's'. */
    abstract public static function key(): string;

    /** The AccessLevel::LEVEL_* this surface belongs to. */
    abstract public static function level(): string;

    /** Translation key for the surface's own name, used by the switcher. */
    abstract public static function label(): string;

    /** <x-icon> name shown beside this surface in the switcher. */
    abstract public static function icon(): string;

    /** Route-name prefix, e.g. 'u.' — every route on this surface starts with it. */
    public static function routePrefix(): string
    {
        return static::key().'.';
    }

    /** The route a switch into this surface lands on. */
    abstract public static function home(): string;

    /**
     * Whether this surface lives inside one fund. Tenant-scoped surfaces take
     * the group as their first route parameter; /s does not.
     */
    abstract public static function tenantScoped(): bool;

    /**
     * @return list<NavSection>
     */
    abstract public static function sections(): array;

    /**
     * Intent → route name, for views this surface shares with another one.
     *
     * See SurfaceRoute: a shared view asks for "show this transaction" and each
     * surface answers with its own route. A surface that does not serve an
     * intent omits it.
     *
     * @return array<string, string>
     */
    public static function routes(): array
    {
        return [];
    }

    /**
     * Every nav item across every section, flattened.
     *
     * @return list<NavItem>
     */
    public static function items(): array
    {
        return array_merge(...array_map(
            fn (NavSection $section) => $section->items,
            static::sections(),
        ));
    }

    /**
     * Whether the current request is being served by this surface, decided from
     * the route name alone so no controller has to declare its own surface.
     */
    public static function isCurrent(): bool
    {
        return request()->routeIs(static::routePrefix().'*');
    }
}
