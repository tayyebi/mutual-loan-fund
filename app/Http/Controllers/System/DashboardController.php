<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('system.dashboard', [
            'userCounts' => [
                'active' => User::where('status', User::STATUS_ACTIVE)->count(),
                'suspended' => User::where('status', User::STATUS_SUSPENDED)->count(),
                'system_admins' => User::where('system_role', User::SYSTEM_ROLE_SYSTEM_ADMIN)->count(),
            ],
            'fundCounts' => [
                'active' => Group::where('status', Group::STATUS_ACTIVE)->count(),
                'suspended' => Group::where('status', Group::STATUS_SUSPENDED)->count(),
            ],
        ]);
    }
}
