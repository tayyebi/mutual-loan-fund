<?php

namespace App\Http\Controllers\System;

use App\Domain\Audit\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

/**
 * Only system-administrator actions appear here — never a fund's own audit
 * trail (member approvals, transaction verification, loan decisions, ledger
 * postings, policy changes). Those stay visible only inside the fund itself,
 * at g.audit.index, to that fund's own administrators.
 */
class AuditController extends Controller
{
    private const SYSTEM_ACTIONS = [
        AuditAction::USER_SUSPENDED,
        AuditAction::USER_REINSTATED,
        AuditAction::SYSTEM_ADMIN_GRANTED,
        AuditAction::SYSTEM_ADMIN_REVOKED,
        AuditAction::GROUP_SUSPENDED,
        AuditAction::GROUP_REINSTATED,
    ];

    public function index(): View
    {
        return view('system.audit.index', [
            'logs' => AuditLog::query()
                ->whereIn('action', self::SYSTEM_ACTIONS)
                ->with('actor')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(40),
        ]);
    }
}
