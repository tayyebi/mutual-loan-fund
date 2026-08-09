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
            abort(403, 'This action is restricted to system administrators.');
        }

        return $next($request);
    }
}
