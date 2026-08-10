<?php

namespace App\Domain\Access;

use App\Models\Group;

/**
 * One destination in a surface's navigation.
 *
 * A nav item is named after what the person wants to do, not after the table it
 * reads. The route it points at is an implementation detail that can change
 * without the label changing.
 */
final class NavItem
{
    /**
     * @param  string  $route  route name, e.g. 'u.money'
     * @param  string  $label  translation key, e.g. 'nav.links.money'
     * @param  string|null  $match  routeIs() pattern for the active state; derived
     *                              from $route when null
     * @param  string|null  $hint  optional translation key for a one-line
     *                             description, used on hub cards
     * @param  string|null  $icon  optional <x-icon> name shown beside the
     *                             label in the sidebar; falls back to a
     *                             generic dot when omitted
     */
    public function __construct(
        public readonly string $route,
        public readonly string $label,
        public readonly ?string $match = null,
        public readonly ?string $hint = null,
        public readonly ?string $icon = null,
    ) {}

    /**
     * The active-state pattern. 'u.money' also lights up for 'u.money.*', and an
     * '.index' route lights up for its whole family, which is what made the old
     * hand-written str_replace in the layout necessary.
     */
    public function matchPattern(): string
    {
        if ($this->match !== null) {
            return $this->match;
        }

        if (str_ends_with($this->route, '.index')) {
            return substr($this->route, 0, -strlen('.index')).'.*';
        }

        return $this->route.'*';
    }

    public function isCurrent(): bool
    {
        return request()->routeIs($this->route) || request()->routeIs($this->matchPattern());
    }

    public function href(?Group $group): string
    {
        return $group instanceof Group
            ? route($this->route, $group)
            : route($this->route);
    }
}
