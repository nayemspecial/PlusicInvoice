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
            // GLOBAL and FIRST of our custom additions — see IdentifyTenant's own
            // docblock for why this must be global rather than route-scoped.
            \App\Http\Middleware\IdentifyTenant::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            // 'tenant' is now the lightweight "was a tenant actually resolved?" check
            // — the real resolution work happens in the global IdentifyTenant above.
            'tenant' => \App\Http\Middleware\RequireTenant::class,
            'tenant.auth' => \App\Http\Middleware\EnsureTenantUserIsAuthenticated::class,
            'tenant.guest' => \App\Http\Middleware\RedirectIfTenantUserAuthenticated::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
