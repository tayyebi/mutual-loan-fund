<?php

namespace App\Http\Controllers;

use App\Domain\Money\Decimal;
use App\Domain\Reports\ReportService;
use App\Domain\Treasuries\ReconciliationService;
use App\Domain\Treasuries\TreasuryService;
use App\Models\Group;
use App\Models\Treasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

class TreasuryController extends Controller
{
    public function index(Group $group, ReportService $reports, ReconciliationService $reconciliations): View
    {
        $rows = $reports->treasuryReport($group);

        return view('treasuries.index', [
            'group' => $group,
            'rows' => $rows->map(fn (array $row) => $row + [
                'latest_reconciliation' => $reconciliations->latest($row['treasury']),
            ]),
        ]);
    }

    public function store(Request $request, Group $group, TreasuryService $treasuries): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:'.Treasury::TYPE_CRYPTO.','.Treasury::TYPE_BANK],
            'currency' => ['required', 'in:'.implode(',', array_keys((array) config('fund.currencies')))],
            'network' => ['nullable', 'in:'.implode(',', array_keys((array) config('fund.networks')))],
            'external_identifier' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $treasuries->create($group, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['type' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.treasuries.index', $group)
            ->with('status', 'Treasury created, with its own ledger account.');
    }

    public function update(Request $request, Group $group, Treasury $treasury, TreasuryService $treasuries): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'external_identifier' => ['nullable', 'string', 'max:200'],
            'status' => ['required', 'in:'.Treasury::STATUS_ACTIVE.','.Treasury::STATUS_INACTIVE],
        ]);

        $treasuries->update($treasury, $data, $request->user());

        return back()->with('status', 'Treasury updated.');
    }

    public function reconcile(
        Request $request,
        Group $group,
        Treasury $treasury,
        ReconciliationService $reconciliations,
    ): RedirectResponse {
        $data = $request->validate([
            'external_balance' => ['required', 'string'],
            'as_of' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $balance = Decimal::parse($data['external_balance']);

        if ($balance === null) {
            return back()->withErrors(['external_balance' => __('exceptions.enter_reported_balance')]);
        }

        $result = $reconciliations->reconcile(
            $treasury,
            $balance,
            Carbon::parse($data['as_of']),
            $request->user(),
            $data['note'] ?? null
        );

        return back()->with('status', $result->isReconciled()
            ? "{$treasury->name} reconciles with the ledger."
            : "{$treasury->name} differs from the ledger by {$result->difference} {$treasury->currency}. The difference has been recorded for investigation.");
    }
}
