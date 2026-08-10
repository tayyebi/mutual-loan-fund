<?php

namespace App\Http\Controllers\Fund;

use App\Domain\Policies\PolicyService;
use App\Domain\Reports\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\View\View;

/**
 * /g/{group} — the fund administrator's console.
 *
 * Opens with the work: what is waiting on a decision. The fund's standing
 * figures matter too, but an administrator arriving here is almost always
 * arriving to clear a queue, so the queues come first and the valuation second.
 */
class HubController extends Controller
{
    public function show(Group $group, ReportService $reports, PolicyService $policies): View
    {
        return view('fund.hub', [
            'group' => $group,
            'summary' => $reports->dashboard($group),
            'policy' => $policies->activePolicy($group),
            'draft' => $policies->draftPolicy($group),
            'queues' => [
                'transactions' => Transaction::query()
                    ->forGroup($group)
                    ->where('status', Transaction::STATUS_PENDING)
                    ->count(),
                'joins' => $group->memberships()
                    ->where('status', GroupMembership::STATUS_REQUESTED)
                    ->count(),
                'loans' => Loan::query()
                    ->forGroup($group)
                    ->whereIn('status', [Loan::STATUS_REQUESTED, Loan::STATUS_APPROVED])
                    ->count(),
            ],
            'goldUnit' => config('fund.gold_unit'),
        ]);
    }
}
