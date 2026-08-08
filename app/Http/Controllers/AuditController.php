<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Group;
use App\Models\PolicyAuditEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request, Group $group): View
    {
        $logs = AuditLog::query()
            ->where('group_id', $group->getKey())
            ->with('actor')
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('audit.index', [
            'group' => $group,
            'logs' => $logs,
            'policyEvents' => PolicyAuditEvent::query()
                ->forGroup($group)
                ->with('actor')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'filters' => $request->only('action'),
        ]);
    }
}
