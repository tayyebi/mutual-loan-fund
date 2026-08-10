<?php

namespace App\Domain\Access\Surfaces;

use App\Domain\Access\AccessLevel;
use App\Domain\Access\NavItem;
use App\Domain\Access\NavSection;
use App\Domain\Access\Surface;

/**
 * /s — running the platform.
 *
 * User and fund lifecycle across the whole application: suspend, reinstate,
 * promote. Nothing here is tenant-scoped and nothing here reads a fund's
 * financial data — see App\Http\Controllers\System\FundController. A system
 * administrator who wants to see a fund's books must be a member of it and go
 * through /u or /g like anyone else.
 */
final class SystemSurface extends Surface
{
    public static function key(): string
    {
        return 's';
    }

    public static function level(): string
    {
        return AccessLevel::LEVEL_SYSTEM_ADMIN;
    }

    public static function label(): string
    {
        return 'nav.surfaces.system_admin';
    }

    public static function home(): string
    {
        return 's.dashboard';
    }

    public static function tenantScoped(): bool
    {
        return false;
    }

    public static function sections(): array
    {
        return [
            new NavSection(null, [
                new NavItem('s.dashboard', 'nav.links.overview', match: 's.dashboard'),
                new NavItem('s.users.index', 'nav.links.users'),
                new NavItem('s.funds.index', 'nav.links.funds'),
                new NavItem('s.audit.index', 'nav.links.audit'),
            ]),
        ];
    }
}
