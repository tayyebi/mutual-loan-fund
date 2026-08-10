<?php

namespace App\Http\Controllers\Member;

use App\Domain\Loans\LoanService;
use App\Domain\Money\Decimal;
use App\Domain\Policies\PolicyService;
use App\Domain\Reports\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Support\GroupContext;
use Illuminate\View\View;

/**
 * /u/{group} — "where do I stand, and is anything waiting on me?"
 *
 * The member's landing page answers those two questions and nothing else. It
 * deliberately does not open with the fund's total value or its pending-
 * verification queue: those are the fund administrator's numbers and live on
 * /g. What the fund is worth as a whole is one click away under "The fund",
 * where a member goes when they are checking on the institution rather than on
 * themselves.
 */
class OverviewController extends Controller
{
    public function show(
        Group $group,
        ReportService $reports,
        PolicyService $policies,
        LoanService $loans,
        GroupContext $context,
    ): View {
        $member = $context->membership();
        $policy = $policies->activePolicy($group);

        return view('member.overview', [
            'group' => $group,
            'policy' => $policy,
            'overview' => $reports->memberOverview($member),
            'recent' => $reports->memberSubmissions($member, 6),
            // Shown as a headline — "you could borrow up to X" — from the same
            // call that will enforce the decision when they actually ask.
            'headroom' => $policy
                ? $loans->eligibility($member, Decimal::zero(), $group->functionalCurrency(), 1, $policy)
                : null,
            'goldUnit' => config('fund.gold_unit'),
        ]);
    }
}
