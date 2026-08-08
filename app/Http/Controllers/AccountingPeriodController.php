<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\PeriodService;
use App\Models\AccountingPeriod;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class AccountingPeriodController extends Controller
{
    public function index(Group $group, PeriodService $periods): View
    {
        return view('periods.index', [
            'group' => $group,
            'periods' => $periods->history($group),
        ]);
    }

    public function close(Group $group, AccountingPeriod $accountingPeriod, PeriodService $periods): RedirectResponse
    {
        try {
            $periods->close($accountingPeriod, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['period' => $e->getMessage()]);
        }

        return back()->with('status', "Period {$accountingPeriod->label()} is closed. Corrections must be posted in an open period.");
    }

    public function reopen(Group $group, AccountingPeriod $accountingPeriod, PeriodService $periods): RedirectResponse
    {
        $periods->reopen($accountingPeriod, request()->user());

        return back()->with('status', "Period {$accountingPeriod->label()} has been reopened. The change is recorded in the audit log.");
    }
}
