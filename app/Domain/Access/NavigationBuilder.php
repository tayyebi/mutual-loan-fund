<?php

namespace App\Domain\Access;

use App\Models\User;
use App\Support\GroupContext;

/**
 * Turns "who is asking, and where are they" into the navigation to render.
 *
 * The only consumer is ShareNavigation. Keeping the decision here rather than in
 * Blade means the rule "higher levels also hold lower ones" is written once.
 */
class NavigationBuilder
{
    public function __construct(private readonly GroupContext $context) {}

    public function build(?User $user): Navigation
    {
        if (! $user instanceof User) {
            return Navigation::none();
        }

        $surface = AccessMap::current();
        $levels = AccessLevel::heldBy($user, $this->context);
        $group = $this->context->has() ? $this->context->group() : null;

        return new Navigation(
            surface: $surface,
            sections: $surface ? $surface::sections() : [],
            switches: $this->switches($levels, $surface),
            group: $group,
        );
    }

    /**
     * Where else this actor can go from here.
     *
     * A tenant-scoped surface is only offered when a fund is in context — there
     * is no /g without a fund to run. The current surface is included so the
     * switcher can render it as the selected one.
     *
     * @param  list<string>  $levels
     * @param  class-string<Surface>|null  $current
     * @return list<SurfaceSwitch>
     */
    private function switches(array $levels, ?string $current): array
    {
        $switches = [];

        foreach (AccessMap::availableTo($levels) as $surface) {
            if ($surface::tenantScoped() && ! $this->context->has()) {
                continue;
            }

            $switches[] = new SurfaceSwitch(
                key: $surface::key(),
                label: $surface::label(),
                href: $surface::tenantScoped()
                    ? route($surface::home(), $this->context->group())
                    : route($surface::home()),
                current: $surface === $current,
                icon: $surface::icon(),
            );
        }

        return $switches;
    }
}
