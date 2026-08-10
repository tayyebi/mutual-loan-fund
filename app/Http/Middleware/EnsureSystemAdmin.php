<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the platform-operations area: user and fund lifecycle, not any
 * fund's financial data. Unrelated to EnsureGroupAdmin, which is per-fund.
 */
class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSystemAdmin()) {
            abort(403, __('exceptions.restricted_to_system_admins'));
        }

        return $next($request);
    }
}
