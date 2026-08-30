<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level enforcement: "this route only makes sense inside a tenant workspace".
 * The actual resolution/DB-switching already happened in the GLOBAL IdentifyTenant
 * middleware (it runs on every request); this just checks whether that succeeded and
 * 404s if not. Deliberately trivial — no DB access, no ordering sensitivity — so it's
 * safe to attach to specific route groups without any priority-list risk.
 */
class RequireTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('currentTenant')) {
            abort(404, 'Workspace not found.');
        }

        return $next($request);
    }
}
