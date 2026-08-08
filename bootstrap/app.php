<?php

use App\Http\Middleware\EnsureGroupAdmin;
use App\Http\Middleware\ResolveGroupContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        //
    })->create();
