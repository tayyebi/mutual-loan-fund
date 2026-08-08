<?php

namespace App\Http\Middleware;

use App\Support\GroupContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the administrative half of a group: approvals, verification, treasury
 * management, accounting and policy.
 */
class EnsureGroupAdmin
{
    public function __construct(private readonly GroupContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->isAdmin()) {
            abort(403, 'This action is restricted to group administrators.');
        }

        return $next($request);
    }
}
