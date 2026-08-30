<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 'tenant' resolves the subdomain and switches the DB connection — apply this
        // to any route group serving a tenant workspace. See app/Http/Middleware/IdentifyTenant.php
        //
        // NOTE: we deliberately do NOT reorder middleware priority here. Route middleware
        // (like 'tenant') already runs AFTER all global 'web' group middleware (including
        // session handling) by Laravel's normal default behavior — which is exactly the
        // order we want. An earlier version of this file tried to force 'tenant' to run
        // before HandleInertiaRequests via prependToPriorityList(), which backfired: it
        // pushed IdentifyTenant to run before session start too, and briefly broke the
        // session table's DB connection. See HandleInertiaRequests — it no longer needs
        // special ordering because IdentifyTenant now shares the tenant user itself.
        $middleware->alias([
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'tenant.auth' => \App\Http\Middleware\EnsureTenantUserIsAuthenticated::class,
            'tenant.guest' => \App\Http\Middleware\RedirectIfTenantUserAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
