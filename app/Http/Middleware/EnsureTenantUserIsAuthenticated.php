<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deliberately NOT Laravel's built-in \Illuminate\Auth\Middleware\Authenticate.
 *
 * That built-in class is part of Laravel's fixed $middlewarePriority list, which
 * caused a real bug: our custom 'tenant' middleware (unlisted) would sometimes get
 * sorted to run AFTER Authenticate despite being registered first in routes/web.php
 * — meaning Auth::guard('tenant')->user() ran before the DB connection was switched,
 * throwing "Unknown database 'unset_tenant_connection'".
 *
 * This class is simple enough that Laravel has no special ordering opinion about it,
 * so it always runs in plain registration order — after 'tenant', as written.
 */
class EnsureTenantUserIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('tenant')->check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
