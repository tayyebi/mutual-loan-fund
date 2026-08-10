<?php

namespace App\Domain\Access\Surfaces;

use App\Domain\Access\AccessLevel;
use App\Domain\Access\NavItem;
use App\Domain\Access\NavSection;
use App\Domain\Access\Surface;

/**
 * /u/{group} — the fund as its members experience it.
 *
 * Every destination here is named after something a member wants, not after the
 * machinery that answers it: "My money" rather than cost-centre statements,
 * "Borrowing" rather than a loans table plus a transactions table, "The fund"
 * rather than a chart of accounts. The double-entry books that make these
 * numbers true are real and auditable, but they are the fund administrator's
 * instrument, and they live on FundAdminSurface.
 *
 * A member never sees a page whose first question is "which ledger account?".
 */
final class MemberSurface extends Surface
{
    public static function key(): string
    {
        return 'u';
    }

    public static function level(): string
    {
        return AccessLevel::LEVEL_MEMBER;
    }

    public static function label(): string
    {
        return 'nav.surfaces.member';
    }

    public static function icon(): string
    {
        return 'user';
    }

    public static function home(): string
    {
        return 'u.dashboard';
    }

    public static function tenantScoped(): bool
    {
        return true;
    }

    public static function routes(): array
    {
        return [
            'transaction.index' => 'u.activity',
            'transaction.show' => 'u.activity.show',
            'transaction.create' => 'u.money.contribute',
            'transaction.store' => 'u.money.store',
            'transaction.receipts.store' => 'u.activity.receipts.store',
            'receipt.show' => 'u.receipts.show',

            'loan.index' => 'u.borrowing',
            'loan.show' => 'u.borrowing.loan',
            'loan.create' => 'u.borrowing.request',
            'loan.store' => 'u.borrowing.store',
            'loan.repay' => 'u.borrowing.repay',
            // Cancelling is the borrower's move; it has no administrative
            // counterpart, so FundAdminSurface deliberately omits it.
            'loan.cancel' => 'u.borrowing.cancel',

            'member.index' => 'u.fund.members',
            'member.show' => 'u.fund.member',
        ];
    }

    public static function sections(): array
    {
        return [
            new NavSection(null, [
                new NavItem(
                    route: 'u.dashboard',
                    label: 'nav.links.overview',
                    match: 'u.dashboard',
                    hint: 'nav.hints.overview',
                    icon: 'home',
                ),
                new NavItem(
                    route: 'u.money',
                    label: 'nav.links.money',
                    match: 'u.money*',
                    hint: 'nav.hints.money',
                    icon: 'wallet',
                ),
                new NavItem(
                    route: 'u.borrowing',
                    label: 'nav.links.borrowing',
                    match: 'u.borrowing*',
                    hint: 'nav.hints.borrowing',
                    icon: 'borrowing',
                ),
                new NavItem(
                    route: 'u.activity',
                    label: 'nav.links.activity',
                    match: 'u.activity*',
                    hint: 'nav.hints.activity',
                    icon: 'activity',
                ),
                new NavItem(
                    route: 'u.wallets.index',
                    label: 'nav.links.wallets',
                    hint: 'nav.hints.wallets',
                    icon: 'wallets',
                ),
                new NavItem(
                    route: 'u.fund',
                    label: 'nav.links.the_fund',
                    match: 'u.fund*',
                    hint: 'nav.hints.the_fund',
                    icon: 'fund',
                ),
            ]),
        ];
    }
}
