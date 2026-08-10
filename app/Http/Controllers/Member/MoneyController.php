<?php

namespace App\Http\Controllers\Member;

use App\Domain\Reports\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Support\GroupContext;
use Illuminate\View\View;

/**
 * /u/{group}/money — "what have I put in, and what is it worth?"
 *
 * The member's own capital, as a running list of what they paid in and one
 * figure for what it is worth today. The cost-centre statement that produces
 * that figure is real and reconcilable, but it is an accounting artefact: a
 * member is shown the payments they made, not the journal lines those payments
 * generated.
 */
class MoneyController extends Controller
{
    public function show(Group $group, ReportService $reports, GroupContext $context): View
    {
        $member = $context->membership();

        return view('member.money', [
            'group' => $group,
            'position' => $reports->memberPosition($member),
            'tracked' => $member->costCenter !== null,
            'contributions' => $reports->memberContributions($member),
            'goldUnit' => config('fund.gold_unit'),
        ]);
    }
}
