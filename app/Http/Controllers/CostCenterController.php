<?php

namespace App\Http\Controllers;

use App\Domain\Groups\CostCenterService;
use App\Domain\Reports\ReportService;
use App\Models\CostCenter;
use App\Models\Group;
use App\Support\GroupContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostCenterController extends Controller
{
    public function index(Group $group): View
    {
        return view('cost-centers.index', [
            'group' => $group,
            'costCenters' => $group->costCenters()->with('member.user')->orderBy('code')->get(),
        ]);
    }

    public function show(Group $group, CostCenter $costCenter, ReportService $reports, GroupContext $context): View
    {
        // A member may look at their own attribution; the rest is administrative.
        if (! $context->isAdmin() && ! $context->owns($costCenter->member)) {
            abort(403);
        }

        return view('cost-centers.show', [
            'group' => $group,
            'costCenter' => $costCenter->load('member.user'),
            'statement' => $reports->costCenterStatement($costCenter),
            'currency' => $group->functionalCurrency(),
        ]);
    }

    public function store(Request $request, Group $group, CostCenterService $costCenters): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $costCenters->create($group, $data['name'], $data['description'] ?? null, $request->user());

        return back()->with('status', 'Cost center created.');
    }
}
