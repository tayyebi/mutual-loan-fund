<?php

namespace App\Domain\Access;

use App\Models\Group;

/**
 * The navigation for one request, already resolved.
 *
 * Shared to every view as $navigation by ShareNavigation, which is why no Blade
 * template builds a link list of its own any more.
 */
final class Navigation
{
    /**
     * @param  class-string<Surface>|null  $surface
     * @param  list<NavSection>  $sections
     * @param  list<SurfaceSwitch>  $switches
     */
    public function __construct(
        public readonly ?string $surface,
        public readonly array $sections,
        public readonly array $switches,
        public readonly ?Group $group,
    ) {}

    public static function none(): self
    {
        return new self(null, [], [], null);
    }

    /** Whether this request has a surface nav to render at all. */
    public function has(): bool
    {
        return $this->sections !== [];
    }

    public function href(NavItem $item): string
    {
        return $item->href($this->group);
    }

    /**
     * Only worth rendering a switcher when there is somewhere else to go — more
     * than one surface, or a single one the actor is not already on. The second
     * case is what puts /s within reach from the cross-fund home, which belongs
     * to no surface itself.
     */
    public function hasSwitches(): bool
    {
        if (count($this->switches) > 1) {
            return true;
        }

        return count($this->switches) === 1 && ! $this->switches[0]->current;
    }
}
