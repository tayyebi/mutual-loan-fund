<?php

namespace App\Domain\Access;

/**
 * A named group of nav items within one surface.
 *
 * The member surface is deliberately one unlabelled section — a member should
 * never have to pick a category to find their own money. Administrative surfaces
 * use labelled sections because their job genuinely has separate areas.
 */
final class NavSection
{
    /**
     * @param  string|null  $label  translation key, or null for the primary
     *                              unlabelled section
     * @param  list<NavItem>  $items
     */
    public function __construct(
        public readonly ?string $label,
        public readonly array $items,
    ) {}

    public function isLabelled(): bool
    {
        return $this->label !== null;
    }
}
