<?php

namespace App\Http\Controllers\Fund;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Treasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Add a treasury" — a short wizard ending at the existing, unchanged
 * TreasuryController::store. Every step here only collects what that action
 * already accepts; no business rule is duplicated.
 */
class TreasuryWizardController extends Controller
{
    public function type(Request $request, Group $group): View
    {
        return view('treasuries.wizard-type', [
            'group' => $group,
            'currencies' => config('fund.currencies'),
            'type' => $request->query('type', Treasury::TYPE_CRYPTO),
            'currency' => $request->query('currency'),
        ]);
    }

    public function typeStore(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.Treasury::TYPE_CRYPTO.','.Treasury::TYPE_BANK],
            'currency' => ['required', 'in:'.implode(',', array_keys((array) config('fund.currencies')))],
        ]);

        return redirect()->route('g.treasuries.add.details', [
            $group,
            'type' => $data['type'],
            'currency' => $data['currency'],
        ]);
    }

    public function details(Request $request, Group $group): View
    {
        return view('treasuries.wizard-details', [
            'group' => $group,
            'networks' => config('fund.networks'),
            'type' => $request->query('type'),
            'currency' => $request->query('currency'),
        ]);
    }
}
