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
     * Whether this actor holds any surface at all worth showing a switcher for
     * — including a single one they are already on, so the switcher always
     * confirms which experience is current rather than appearing and
     * disappearing as an actor moves between surfaces.
     */
    public function hasSwitches(): bool
    {
        return $this->switches !== [];
    }
}
