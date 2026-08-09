<?php

namespace App\Http\Controllers\Admin;

use App\Domain\SystemAdmin\SystemAdminService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A fund's own financial data — its members, transactions, loans and ledger —
 * is never read here. Only the metadata needed to operate the platform: name,
 * status, who created it, and how many people belong to it.
 */
class FundController extends Controller
{
    public function index(): View
    {
        return view('admin.funds.index', [
            'funds' => Group::query()
                ->withCount('activeMemberships')
                ->with('creator')
                ->orderBy('name')
                ->paginate(40)
                ->withQueryString(),
        ]);
    }

    public function show(Group $group): View
    {
        return view('admin.funds.show', [
            'fund' => $group->loadCount('activeMemberships')->load('creator'),
        ]);
    }

    public function suspend(Group $group, SystemAdminService $systemAdmin, Request $request): RedirectResponse
    {
        $systemAdmin->suspendFund($group, $request->user());

        return back()->with('status', "{$group->name} has been suspended.");
    }

    public function reinstate(Group $group, SystemAdminService $systemAdmin, Request $request): RedirectResponse
    {
        $systemAdmin->reinstateFund($group, $request->user());

        return back()->with('status', "{$group->name} has been reinstated.");
    }
}
