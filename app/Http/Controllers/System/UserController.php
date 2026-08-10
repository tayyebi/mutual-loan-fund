<?php

namespace App\Http\Controllers\System;

use App\Domain\SystemAdmin\SystemAdminService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    public function index(): View
    {
        return view('system.users.index', [
            'users' => User::query()
                ->withCount('memberships')
                ->orderBy('name')
                ->paginate(40)
                ->withQueryString(),
        ]);
    }

    public function show(User $user): View
    {
        return view('system.users.show', [
            'user' => $user,
            'memberships' => $user->memberships()->with('group')->get(),
        ]);
    }

    public function suspend(User $user, SystemAdminService $systemAdmin, Request $request): RedirectResponse
    {
        try {
            $systemAdmin->suspendUser($user, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['user' => $e->getMessage()]);
        }

        return back()->with('status', "{$user->name} has been suspended.");
    }

    public function reinstate(User $user, SystemAdminService $systemAdmin, Request $request): RedirectResponse
    {
        $systemAdmin->reinstateUser($user, $request->user());

        return back()->with('status', "{$user->name} has been reinstated.");
    }

    public function promote(User $user, SystemAdminService $systemAdmin, Request $request): RedirectResponse
    {
        $systemAdmin->promote($user, $request->user());

        return back()->with('status', "{$user->name} is now a system administrator.");
    }

    public function demote(User $user, SystemAdminService $systemAdmin, Request $request): RedirectResponse
    {
        try {
            $systemAdmin->demote($user, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['user' => $e->getMessage()]);
        }

        return back()->with('status', "{$user->name} is no longer a system administrator.");
    }
}
