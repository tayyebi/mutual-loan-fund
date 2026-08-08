<?php

namespace App\Http\Controllers;

use App\Domain\Reports\ReportService;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Report keys and their titles. Adding a report means adding a case here and
     * a view; nothing else changes.
     */
    private const REPORTS = [
        'trial-balance' => 'Trial Balance',
        'balance-sheet' => 'Balance Sheet',
        'income-statement' => 'Income Statement',
        'treasuries' => 'Treasury Report',
        'receivables' => 'Loan Receivables',
        'cost-centers' => 'Cost Center Statements',
        'gold' => 'Gold Valuation',
    ];

    public function index(Group $group): View
    {
        return view('reports.index', ['group' => $group, 'reports' => self::REPORTS]);
    }

    public function show(Request $request, Group $group, string $report, ReportService $reports): View
    {
        abort_unless(array_key_exists($report, self::REPORTS), 404);

        $asOf = $request->query('as_of') ? Carbon::parse($request->query('as_of')) : null;
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : null;

        $data = match ($report) {
            'trial-balance' => [
                'rows' => $reports->trialBalance($group, $asOf),
                'totals' => $reports->trialBalanceTotals($group, $asOf),
            ],
            'balance-sheet' => [
                'sheet' => $reports->balanceSheet($group, $asOf),
                'rows' => $reports->trialBalance($group, $asOf),
            ],
            'income-statement' => ['statement' => $reports->incomeStatement($group, $from, $asOf)],
            'treasuries' => ['rows' => $reports->treasuryReport($group, $asOf)],
            'receivables' => ['rows' => $reports->loanReceivables($group, $asOf)],
            'cost-centers' => ['costCenters' => $group->costCenters()->with('member.user')->orderBy('code')->get()],
            'gold' => ['valuation' => $reports->goldValuation($group, $asOf)],
        };

        return view("reports.{$report}", $data + [
            'group' => $group,
            'title' => self::REPORTS[$report],
            'asOf' => $asOf,
            'from' => $from,
            'currency' => $group->functionalCurrency(),
            'goldUnit' => config('fund.gold_unit'),
        ]);
    }
}
