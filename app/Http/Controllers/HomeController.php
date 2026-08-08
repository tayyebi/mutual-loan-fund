<?php

namespace App\Http\Controllers;

use App\Models\GroupMembership;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The account's funds. There is no public discovery: a user sees only the
     * groups they belong to or have asked to join.
     */
    public function index(Request $request): View
    {
        $memberships = $request->user()
            ->memberships()
            ->with('group')
            ->whereIn('status', [
                GroupMembership::STATUS_ACTIVE,
                GroupMembership::STATUS_APPROVED,
                GroupMembership::STATUS_REQUESTED,
            ])
            ->get()
            ->sortBy(fn (GroupMembership $membership) => $membership->group->name);

        return view('home', ['memberships' => $memberships]);
    }
}
