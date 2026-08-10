<?php

namespace App\Http\Controllers\Member;

use App\Domain\Frameworks\FrameworkComplianceChecker;
use App\Domain\Policies\PolicyService;
use App\Domain\Reports\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\View\View;

/**
 * /u/{group}/fund — "is this fund healthy, and what are its rules?"
 *
 * The transparency corner. A mutual fund only works if the people whose money is
 * in it can check on it, so these figures are the same ones the administrator
 * sees: what the fund holds, where it holds it, what is lent out, who the
 * members are, and the rules everyone agreed to.
 *
 * What is deliberately absent is the apparatus — journal entries, the chart of
 * accounts, cost centres, accounting periods. Those live on /g not because they
 * are secret but because reading them is a bookkeeping skill, and a member who
 * wants to verify the fund is better served by the totals above than by being
 * handed a trial balance.
 */
class FundController extends Controller
{
    public function show(Group $group, ReportService $reports, PolicyService $policies): View
    {
        return view('member.fund', [
            'group' => $group,
            'summary' => $reports->dashboard($group),
            'policy' => $policies->activePolicy($group),
            'memberCount' => $group->memberships()
                ->whereIn('status', [GroupMembership::STATUS_ACTIVE, GroupMembership::STATUS_APPROVED])
                ->count(),
            'goldUnit' => config('fund.gold_unit'),
        ]);
    }

    /**
     * The rules in force, as a member reads them: the active version only.
     * Draft versions and the history behind them are administrative.
     */
    public function rules(Group $group, PolicyService $policies, FrameworkComplianceChecker $frameworks): View
    {
        $active = $policies->activePolicy($group);

        return view('member.rules', [
            'group' => $group,
            'policy' => $active,
            'config' => $active?->toPolicyConfig(),
            'framework_warnings' => $active
                ? $frameworks->check($active->toPolicyConfig(), $group->financialFramework)
                : [],
        ]);
    }
}
