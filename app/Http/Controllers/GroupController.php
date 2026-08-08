<?php

namespace App\Http\Controllers;

use App\Domain\Groups\GroupService;
use App\Domain\Groups\MembershipService;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class GroupController extends Controller
{
    public function create(): View
    {
        return view('groups.create');
    }

    public function store(Request $request, GroupService $groups): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $group = $groups->create($request->user(), $data['name'], $data['description'] ?? null);

        return redirect()
            ->route('g.dashboard', $group)
            ->with('status', "{$group->name} is ready. Its chart of accounts and policy v1 are in place.");
    }

    /**
     * The membership request page for someone who is not yet a member. Reached
     * by a link to the fund's slug; funds are not discoverable.
     */
    public function showJoin(Request $request, Group $group): View|RedirectResponse
    {
        $membership = $request->user()->membershipFor($group);

        if ($membership?->hasAccess()) {
            return redirect()->route('g.dashboard', $group);
        }

        return view('groups.join', ['group' => $group, 'membership' => $membership]);
    }

    public function join(Request $request, Group $group, MembershipService $memberships): RedirectResponse
    {
        if (! $group->isActive()) {
            return back()->withErrors(['group' => 'This fund is not accepting members.']);
        }

        try {
            $membership = $memberships->request($group, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['group' => $e->getMessage()]);
        }

        return $membership->hasAccess()
            ? redirect()->route('g.dashboard', $group)
            : redirect()->route('home')->with('status', 'Your membership request has been sent to the administrators.');
    }
}
