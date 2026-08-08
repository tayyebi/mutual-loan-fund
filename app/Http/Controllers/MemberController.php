<?php

namespace App\Http\Controllers;

use App\Domain\Groups\MembershipService;
use App\Domain\Reports\ReportService;
use App\Models\Group;
use App\Models\GroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MemberController extends Controller
{
    public function index(Group $group): View
    {
        return view('members.index', [
            'group' => $group,
            'members' => $group->memberships()
                ->with(['user', 'costCenter'])
                ->whereIn('status', [GroupMembership::STATUS_ACTIVE, GroupMembership::STATUS_APPROVED, GroupMembership::STATUS_SUSPENDED])
                ->get()
                ->sortBy(fn (GroupMembership $m) => $m->displayName()),
            'pending' => $group->memberships()->where('status', GroupMembership::STATUS_REQUESTED)->count(),
        ]);
    }

    public function requests(Group $group): View
    {
        return view('members.requests', [
            'group' => $group,
            'requests' => $group->memberships()
                ->with('user')
                ->where('status', GroupMembership::STATUS_REQUESTED)
                ->orderBy('requested_at')
                ->get(),
        ]);
    }

    public function show(Group $group, GroupMembership $membership, ReportService $reports): View
    {
        $this->authorize('viewMemberPosition', [$group, $membership]);

        return view('members.show', [
            'group' => $group,
            'member' => $membership->load(['user', 'costCenter', 'wallets']),
            'position' => $reports->memberPosition($membership),
            'loans' => $membership->loans()->orderByDesc('id')->get(),
        ]);
    }

    public function approve(Group $group, GroupMembership $membership, MembershipService $memberships): RedirectResponse
    {
        $memberships->approve($membership, request()->user());

        return back()->with('status', "{$membership->displayName()} is now a member and has a cost center.");
    }

    public function reject(Request $request, Group $group, GroupMembership $membership, MembershipService $memberships): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $memberships->reject($membership, $request->user(), $data['reason'] ?? null);

        return back()->with('status', 'The membership request was rejected.');
    }

    public function suspend(Group $group, GroupMembership $membership, MembershipService $memberships): RedirectResponse
    {
        try {
            $memberships->suspend($membership, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('status', "{$membership->displayName()} has been suspended.");
    }

    public function reinstate(Group $group, GroupMembership $membership, MembershipService $memberships): RedirectResponse
    {
        $memberships->reinstate($membership, request()->user());

        return back()->with('status', "{$membership->displayName()} has been reinstated.");
    }

    public function changeRole(Request $request, Group $group, GroupMembership $membership, MembershipService $memberships): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:'.GroupMembership::ROLE_MEMBER.','.GroupMembership::ROLE_ADMIN],
        ]);

        try {
            $memberships->changeRole($membership, $data['role'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['membership' => $e->getMessage()]);
        }

        return back()->with('status', "{$membership->displayName()} is now a {$data['role']}.");
    }
}
