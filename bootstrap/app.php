<?php

use App\Http\Middleware\EnsureGroupAdmin;
use App\Http\Middleware\ResolveGroupContext;
use App\Support\DomainRefusal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        console: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // Runs after SubstituteBindings so it can verify that every bound
            // model actually belongs to the group in the URL (tenant isolation).
            'group' => ResolveGroupContext::class,
            'group.admin' => EnsureGroupAdmin::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
        | Domain refusals are answers, not crashes. A missing exchange rate, an
        | absent policy or a closed period each mean "this operation cannot be
        | recorded yet", and the operator needs to read that sentence rather
        | than a stack trace. Nothing was written in any of these cases: the
        | surrounding database transaction has already rolled back.
        */
        $exceptions->render(function (DomainRefusal $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withInput()->withErrors(['operation' => $e->getMessage()]);
        });
    })->create();
