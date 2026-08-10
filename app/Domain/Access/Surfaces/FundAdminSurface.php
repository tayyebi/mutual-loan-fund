<?php

namespace App\Domain\Access\Surfaces;

use App\Domain\Access\AccessLevel;
use App\Domain\Access\NavItem;
use App\Domain\Access\NavSection;
use App\Domain\Access\Surface;

/**
 * /g/{group} — running the fund.
 *
 * This is an operator's console and is allowed to speak the machinery's own
 * language: journal entries, cost centres, accounting periods, policy versions.
 * The whole prefix sits behind 'group' + 'group.admin', so reaching any of it
 * requires GroupMembership::ROLE_ADMIN in *this* fund. Being a system
 * administrator elsewhere grants nothing here.
 *
 * A fund administrator is also an investor, and does their own contributing and
 * borrowing on MemberSurface, not here.
 */
final class FundAdminSurface extends Surface
{
    public static function key(): string
    {
        return 'g';
    }

    public static function level(): string
    {
        return AccessLevel::LEVEL_FUND_ADMIN;
    }

    public static function label(): string
    {
        return 'nav.surfaces.fund_admin';
    }

    public static function icon(): string
    {
        return 'briefcase';
    }

    public static function home(): string
    {
        return 'g.dashboard';
    }

    public static function tenantScoped(): bool
    {
        return true;
    }

    public static function routes(): array
    {
        return [
            'transaction.index' => 'g.transactions.index',
            'transaction.show' => 'g.transactions.show',
            'transaction.create' => 'g.transactions.create',
            'transaction.store' => 'g.transactions.store',
            'transaction.receipts.store' => 'g.transactions.receipts.store',
            'receipt.show' => 'g.receipts.show',

            'loan.index' => 'g.loans.index',
            'loan.show' => 'g.loans.show',
            'loan.repay' => 'g.loans.repay',

            'member.index' => 'g.members.index',
            'member.show' => 'g.members.show',

            /*
            | Decisions. MemberSurface declares none of these, which is what
            | makes @surfaces('transaction.verify') the right guard inside a
            | shared view: the question is "does this experience do that?",
            | not "does this person happen to hold the role?". A fund
            | administrator reading their own contribution on /u is wearing
            | their investor hat and is not offered the verify button.
            */
            'transaction.verify' => 'g.transactions.verify',
            'transaction.reject' => 'g.transactions.reject',
            'transaction.chain-check' => 'g.transactions.chain-check',

            'loan.approve' => 'g.loans.approve',
            'loan.reject' => 'g.loans.reject',
            'loan.disburse' => 'g.loans.disburse',

            'member.requests' => 'g.members.requests',
            'member.approve' => 'g.members.approve',
            'member.reject' => 'g.members.reject',
            'member.suspend' => 'g.members.suspend',
            'member.reinstate' => 'g.members.reinstate',
            'member.role' => 'g.members.role',

            // The books. Members reach the rules through u.fund.rules and the
            // fund's standing through u.fund; the underlying documents are the
            // administrator's instrument.
            'policy.show' => 'g.policies.show',
            'ledger.show' => 'g.ledger.show',
            'account.show' => 'g.accounts.show',
            'cost-center.show' => 'g.cost-centers.show',
        ];
    }

    public static function sections(): array
    {
        return [
            new NavSection(null, [
                new NavItem('g.dashboard', 'nav.links.manage', match: 'g.dashboard', icon: 'dashboard'),
            ]),

            new NavSection('nav.sections.operations', [
                new NavItem('g.transactions.index', 'nav.links.activity', icon: 'activity'),
                new NavItem('g.loans.index', 'nav.links.loans', icon: 'loans'),
                new NavItem('g.members.index', 'nav.links.members', icon: 'members'),
                new NavItem('g.treasuries.index', 'nav.links.treasuries', icon: 'treasuries'),
            ]),

            new NavSection('nav.sections.books', [
                new NavItem('g.ledger.index', 'nav.links.ledger', icon: 'ledger'),
                new NavItem('g.accounts.index', 'nav.links.accounts', icon: 'accounts'),
                new NavItem('g.cost-centers.index', 'nav.links.cost_centers', icon: 'cost-centers'),
                new NavItem('g.periods.index', 'nav.links.periods', icon: 'periods'),
                new NavItem('g.reports.index', 'nav.links.reports', icon: 'reports'),
            ]),

            new NavSection('nav.sections.governance', [
                new NavItem('g.policies.index', 'nav.links.policies', icon: 'policies'),
                new NavItem('g.framework.edit', 'nav.links.framework', match: 'g.framework.*', icon: 'framework'),
                new NavItem('g.audit.index', 'nav.links.audit', icon: 'audit'),
            ]),
        ];
    }
}
