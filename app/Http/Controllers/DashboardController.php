<?php

namespace App\Http\Controllers;

use App\Domain\Policies\PolicyService;
use App\Domain\Reports\ReportService;
use App\Models\Group;
use App\Support\GroupContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(
        Group $group,
        ReportService $reports,
        PolicyService $policies,
        GroupContext $context,
    ): View {
        return view('dashboard', [
            'group' => $group,
            'summary' => $reports->dashboard($group),
            'position' => $reports->memberPosition($context->membership()),
            'policy' => $policies->activePolicy($group),
            'goldUnit' => config('fund.gold_unit'),
        ]);
    }
}
